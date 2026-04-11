/**
 * Visual Builder Pro - App Module: Design Tokens
 * Sistema completo de Design Tokens con soporte para:
 * - Figma Tokens
 * - Style Dictionary
 * - W3C Design Tokens Format
 * - Sincronizacion con Figma
 * - Exportacion multiple (CSS, SCSS, Tailwind, JS)
 *
 * @package Flavor_Platform
 * @since 2.2.0
 */

window.VBPAppDesignTokens = {
    // Estado
    showTokensPanel: false,
    activeTokenCategory: 'colors',
    tokenSearchQuery: '',
    syncStatus: 'idle', // 'idle' | 'syncing' | 'success' | 'error'
    lastSyncTime: null,
    figmaFileKey: null,
    figmaAccessToken: null,

    // Temas disponibles (sincronizados desde Flavor_Theme_Manager)
    availableThemes: [],
    activeThemeId: 'default',

    // Historial de sincronizaciones
    syncHistory: [],

    // Tokens pendientes de aplicar (diff)
    pendingTokenChanges: [],
    showDiffPreview: false,

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
            // Asegurar 6 digitos
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
    },

    // ============ FORMATOS ESTANDAR ============

    /**
     * Importar tokens desde formato Figma Tokens
     * @param {Object} figmaTokens - Objeto de tokens en formato Figma Tokens plugin
     */
    importFromFigmaTokens: function(figmaTokens) {
        var self = this;
        var convertedTokens = {};

        try {
            // Figma Tokens usa una estructura anidada por categorias
            Object.keys(figmaTokens).forEach(function(category) {
                var categoryTokens = figmaTokens[category];

                if (typeof categoryTokens === 'object' && categoryTokens !== null) {
                    self.processNestedTokens(categoryTokens, '--flavor-', convertedTokens);
                }
            });

            // Calcular diff antes de aplicar
            this.pendingTokenChanges = this.calculateTokenDiff(convertedTokens);
            this.showDiffPreview = true;

            return {
                success: true,
                tokensCount: Object.keys(convertedTokens).length,
                changes: this.pendingTokenChanges
            };

        } catch (error) {
            vbpLog.error('Error importando Figma Tokens:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Procesar tokens anidados recursivamente
     */
    processNestedTokens: function(tokens, prefix, result) {
        var self = this;

        Object.keys(tokens).forEach(function(key) {
            var value = tokens[key];
            var tokenKey = prefix + key.toLowerCase().replace(/\s+/g, '-');

            if (value && typeof value === 'object') {
                // Si tiene propiedad $value, es un token (formato W3C)
                if (value.$value !== undefined) {
                    result[tokenKey] = self.resolveTokenValue(value.$value, value.$type);
                }
                // Si tiene propiedad value, es formato Figma Tokens
                else if (value.value !== undefined) {
                    result[tokenKey] = self.resolveTokenValue(value.value, value.type);
                }
                // Si es un objeto anidado, procesar recursivamente
                else if (!value.$type && !value.type) {
                    self.processNestedTokens(value, tokenKey + '-', result);
                }
            }
        });
    },

    /**
     * Resolver valor de token (puede ser referencia a otro token)
     */
    resolveTokenValue: function(value, type) {
        // Si es una referencia a otro token {category.token}
        if (typeof value === 'string' && value.startsWith('{') && value.endsWith('}')) {
            var refPath = value.slice(1, -1);
            // Convertir referencia a variable CSS
            return 'var(--flavor-' + refPath.replace(/\./g, '-').toLowerCase() + ')';
        }

        // Convertir segun tipo
        switch(type) {
            case 'color':
                return this.normalizeColor(value);
            case 'dimension':
            case 'spacing':
            case 'sizing':
                return this.normalizeDimension(value);
            case 'fontFamily':
            case 'fontFamilies':
                return Array.isArray(value) ? value.join(', ') : value;
            case 'fontWeight':
            case 'fontWeights':
                return this.normalizeFontWeight(value);
            case 'boxShadow':
                return this.normalizeBoxShadow(value);
            default:
                return value;
        }
    },

    /**
     * Normalizar dimension
     */
    normalizeDimension: function(value) {
        if (typeof value === 'number') {
            return value + 'px';
        }
        return value;
    },

    /**
     * Normalizar font weight
     */
    normalizeFontWeight: function(value) {
        var weights = {
            'thin': 100,
            'extralight': 200,
            'light': 300,
            'regular': 400,
            'medium': 500,
            'semibold': 600,
            'bold': 700,
            'extrabold': 800,
            'black': 900
        };
        var lowerValue = String(value).toLowerCase();
        return weights[lowerValue] || value;
    },

    /**
     * Normalizar box shadow
     */
    normalizeBoxShadow: function(value) {
        if (Array.isArray(value)) {
            return value.map(this.formatSingleShadow.bind(this)).join(', ');
        }
        if (typeof value === 'object') {
            return this.formatSingleShadow(value);
        }
        return value;
    },

    /**
     * Formatear una sombra individual
     */
    formatSingleShadow: function(shadowObj) {
        if (!shadowObj) return 'none';
        var x = shadowObj.x || shadowObj.offsetX || 0;
        var y = shadowObj.y || shadowObj.offsetY || 0;
        var blur = shadowObj.blur || 0;
        var spread = shadowObj.spread || 0;
        var color = shadowObj.color || 'rgba(0,0,0,0.1)';
        var inset = shadowObj.type === 'innerShadow' ? 'inset ' : '';
        return inset + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
    },

    /**
     * Importar desde formato Style Dictionary
     * @param {Object} styleDict - Propiedades en formato Style Dictionary
     */
    importFromStyleDictionary: function(styleDict) {
        var self = this;
        var convertedTokens = {};

        try {
            // Style Dictionary usa formato plano con nombres tipo color.primary.value
            if (styleDict.properties) {
                this.processStyleDictProps(styleDict.properties, '', convertedTokens);
            } else {
                this.processStyleDictProps(styleDict, '', convertedTokens);
            }

            this.pendingTokenChanges = this.calculateTokenDiff(convertedTokens);
            this.showDiffPreview = true;

            return {
                success: true,
                tokensCount: Object.keys(convertedTokens).length,
                changes: this.pendingTokenChanges
            };

        } catch (error) {
            vbpLog.error('Error importando Style Dictionary:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Procesar propiedades de Style Dictionary
     */
    processStyleDictProps: function(props, prefix, result) {
        var self = this;

        Object.keys(props).forEach(function(key) {
            var value = props[key];
            var propKey = prefix ? prefix + '-' + key : key;
            var tokenKey = '--flavor-' + propKey.toLowerCase();

            if (value && typeof value === 'object') {
                if (value.value !== undefined) {
                    result[tokenKey] = self.resolveTokenValue(value.value, value.type || value.attributes?.category);
                } else {
                    self.processStyleDictProps(value, propKey, result);
                }
            }
        });
    },

    /**
     * Importar desde formato W3C Design Tokens
     * @param {Object} w3cTokens - Tokens en formato W3C
     */
    importFromW3CFormat: function(w3cTokens) {
        // W3C usa $value y $type como prefijos
        return this.importFromFigmaTokens(w3cTokens); // Mismo formato base
    },

    /**
     * Calcular diferencias entre tokens actuales y nuevos
     */
    calculateTokenDiff: function(newTokens) {
        var diff = [];
        var currentTokens = this.documentTokenOverrides;
        var self = this;

        // Tokens nuevos o modificados
        Object.keys(newTokens).forEach(function(key) {
            var currentValue = self.getTokenValue(key);
            var newValue = newTokens[key];

            if (!currentTokens[key]) {
                diff.push({
                    key: key,
                    type: 'add',
                    oldValue: currentValue,
                    newValue: newValue
                });
            } else if (currentTokens[key] !== newValue) {
                diff.push({
                    key: key,
                    type: 'modify',
                    oldValue: currentTokens[key],
                    newValue: newValue
                });
            }
        });

        // Tokens eliminados
        Object.keys(currentTokens).forEach(function(key) {
            if (!newTokens[key]) {
                diff.push({
                    key: key,
                    type: 'remove',
                    oldValue: currentTokens[key],
                    newValue: null
                });
            }
        });

        return diff;
    },

    /**
     * Aplicar cambios pendientes
     */
    applyPendingChanges: function() {
        var self = this;

        this.pendingTokenChanges.forEach(function(change) {
            if (change.type === 'remove') {
                self.resetToken(change.key);
            } else {
                self.updateToken(change.key, change.newValue);
            }
        });

        this.pendingTokenChanges = [];
        this.showDiffPreview = false;
        this.showNotification('Tokens actualizados correctamente', 'success');
    },

    /**
     * Cancelar cambios pendientes
     */
    cancelPendingChanges: function() {
        this.pendingTokenChanges = [];
        this.showDiffPreview = false;
    },

    // ============ SINCRONIZACION CON FIGMA ============

    /**
     * Configurar conexion con Figma
     */
    configureFigmaSync: function(fileKey, accessToken) {
        this.figmaFileKey = fileKey;
        this.figmaAccessToken = accessToken;

        // Guardar en settings
        var store = Alpine.store('vbp');
        if (store && store.settings) {
            store.settings.figmaSync = {
                fileKey: fileKey,
                enabled: true
            };
            store.isDirty = true;
        }
    },

    /**
     * Sincronizar tokens desde Figma
     */
    syncFromFigma: function() {
        var self = this;

        if (!this.figmaFileKey) {
            this.showNotification('Configura primero la conexion con Figma', 'error');
            return Promise.reject('No Figma file configured');
        }

        this.syncStatus = 'syncing';

        // Llamar al endpoint del servidor para obtener tokens de Figma
        return fetch(VBP_Config.restUrl.replace('/documents', '/tokens/sync-figma'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({
                file_key: this.figmaFileKey
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success && result.tokens) {
                self.syncStatus = 'success';
                self.lastSyncTime = new Date();

                // Agregar al historial
                self.syncHistory.unshift({
                    timestamp: self.lastSyncTime.toISOString(),
                    tokensCount: Object.keys(result.tokens).length,
                    source: 'figma'
                });

                // Importar tokens
                return self.importFromFigmaTokens(result.tokens);
            } else {
                throw new Error(result.message || 'Error sincronizando');
            }
        })
        .catch(function(error) {
            self.syncStatus = 'error';
            vbpLog.error('Error sincronizando con Figma:', error);
            self.showNotification('Error sincronizando: ' + error.message, 'error');
            return { success: false, error: error.message };
        });
    },

    // ============ EXPORTACION MULTIPLE ============

    /**
     * Exportar tokens en formato especificado
     */
    exportTokensAs: function(format) {
        switch(format) {
            case 'css':
                return this.exportAsCSS();
            case 'scss':
                return this.exportAsSCSS();
            case 'tailwind':
                return this.exportAsTailwind();
            case 'js':
                return this.exportAsJS();
            case 'json':
                return this.exportAsJSON();
            case 'figma':
                return this.exportAsFigmaTokens();
            case 'w3c':
                return this.exportAsW3C();
            default:
                return this.exportAsCSS();
        }
    },

    /**
     * Exportar como CSS Variables
     */
    exportAsCSS: function() {
        var css = '/**\n * Flavor Design Tokens\n * Generated: ' + new Date().toISOString() + '\n */\n\n';
        css += ':root {\n';

        var self = this;
        var allTokens = this.getAllTokenValues();

        Object.keys(allTokens).forEach(function(key) {
            css += '  ' + key + ': ' + allTokens[key] + ';\n';
        });

        css += '}\n';

        this.downloadFile(css, 'flavor-tokens.css', 'text/css');
        return css;
    },

    /**
     * Exportar como SCSS Variables
     */
    exportAsSCSS: function() {
        var scss = '//\n// Flavor Design Tokens\n// Generated: ' + new Date().toISOString() + '\n//\n\n';

        var self = this;
        var allTokens = this.getAllTokenValues();

        // Variables SCSS
        Object.keys(allTokens).forEach(function(key) {
            var scssKey = '$' + key.replace('--flavor-', 'flavor-');
            scss += scssKey + ': ' + allTokens[key] + ';\n';
        });

        scss += '\n// CSS Custom Properties\n';
        scss += ':root {\n';
        Object.keys(allTokens).forEach(function(key) {
            var scssKey = '$' + key.replace('--flavor-', 'flavor-');
            scss += '  ' + key + ': #{' + scssKey + '};\n';
        });
        scss += '}\n';

        // Mixin para usar tokens
        scss += '\n// Mixin for using tokens\n';
        scss += '@mixin flavor-token($property, $token) {\n';
        scss += '  #{$property}: var(--flavor-#{$token});\n';
        scss += '}\n';

        this.downloadFile(scss, 'flavor-tokens.scss', 'text/scss');
        return scss;
    },

    /**
     * Exportar como configuracion de Tailwind
     */
    exportAsTailwind: function() {
        var allTokens = this.getAllTokenValues();
        var config = {
            theme: {
                extend: {
                    colors: {},
                    spacing: {},
                    borderRadius: {},
                    fontFamily: {},
                    fontSize: {},
                    boxShadow: {}
                }
            }
        };

        Object.keys(allTokens).forEach(function(key) {
            var value = allTokens[key];
            var cleanKey = key.replace('--flavor-', '');
            var cssVarRef = 'var(' + key + ')';

            // Categorizar por tipo de token
            if (cleanKey.includes('primary') || cleanKey.includes('secondary') ||
                cleanKey.includes('success') || cleanKey.includes('warning') ||
                cleanKey.includes('error') || cleanKey.includes('info') ||
                cleanKey.includes('bg') || cleanKey.includes('text') ||
                cleanKey.includes('border')) {
                config.theme.extend.colors[cleanKey] = cssVarRef;
            } else if (cleanKey.includes('spacing')) {
                config.theme.extend.spacing[cleanKey.replace('spacing-', '')] = cssVarRef;
            } else if (cleanKey.includes('radius')) {
                config.theme.extend.borderRadius[cleanKey.replace('radius-', '')] = cssVarRef;
            } else if (cleanKey.includes('font-family')) {
                config.theme.extend.fontFamily[cleanKey.replace('font-family-', '')] = cssVarRef;
            } else if (cleanKey.includes('font-size')) {
                config.theme.extend.fontSize[cleanKey.replace('font-size-', '')] = cssVarRef;
            } else if (cleanKey.includes('shadow')) {
                config.theme.extend.boxShadow[cleanKey.replace('shadow-', '')] = cssVarRef;
            }
        });

        var output = '// Flavor Design Tokens - Tailwind Config\n';
        output += '// Generated: ' + new Date().toISOString() + '\n\n';
        output += 'module.exports = ' + JSON.stringify(config, null, 2) + ';\n';

        this.downloadFile(output, 'flavor-tokens.tailwind.js', 'application/javascript');
        return output;
    },

    /**
     * Exportar como objeto JavaScript
     */
    exportAsJS: function() {
        var allTokens = this.getAllTokenValues();
        var tokens = {};

        Object.keys(allTokens).forEach(function(key) {
            var cleanKey = key.replace('--flavor-', '').replace(/-([a-z])/g, function(g) { return g[1].toUpperCase(); });
            tokens[cleanKey] = allTokens[key];
        });

        var output = '/**\n * Flavor Design Tokens\n * Generated: ' + new Date().toISOString() + '\n */\n\n';
        output += 'export const flavorTokens = ' + JSON.stringify(tokens, null, 2) + ';\n\n';
        output += 'export default flavorTokens;\n';

        this.downloadFile(output, 'flavor-tokens.js', 'application/javascript');
        return output;
    },

    /**
     * Exportar como JSON
     */
    exportAsJSON: function() {
        var exportData = {
            $schema: 'https://design-tokens.github.io/community-group/format/',
            flavor: {
                version: '2.2.0',
                generatedAt: new Date().toISOString(),
                source: 'Visual Builder Pro'
            },
            tokens: {}
        };

        var allTokens = this.getAllTokenValues();

        Object.keys(allTokens).forEach(function(key) {
            var cleanKey = key.replace('--flavor-', '');
            exportData.tokens[cleanKey] = {
                $value: allTokens[key],
                $type: this.inferTokenType(key)
            };
        }.bind(this));

        var output = JSON.stringify(exportData, null, 2);
        this.downloadFile(output, 'flavor-tokens.json', 'application/json');
        return output;
    },

    /**
     * Exportar en formato Figma Tokens
     */
    exportAsFigmaTokens: function() {
        var allTokens = this.getAllTokenValues();
        var figmaTokens = {};

        Object.keys(allTokens).forEach(function(key) {
            var cleanKey = key.replace('--flavor-', '');
            var parts = cleanKey.split('-');
            var category = parts[0];

            if (!figmaTokens[category]) {
                figmaTokens[category] = {};
            }

            var tokenName = parts.slice(1).join('-') || 'default';
            figmaTokens[category][tokenName] = {
                value: allTokens[key],
                type: this.inferTokenType(key)
            };
        }.bind(this));

        var output = JSON.stringify(figmaTokens, null, 2);
        this.downloadFile(output, 'flavor-tokens.figma.json', 'application/json');
        return output;
    },

    /**
     * Exportar en formato W3C Design Tokens
     */
    exportAsW3C: function() {
        var allTokens = this.getAllTokenValues();
        var w3cTokens = {};

        Object.keys(allTokens).forEach(function(key) {
            var cleanKey = key.replace('--flavor-', '');
            var parts = cleanKey.split('-');
            var current = w3cTokens;

            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }

            var lastPart = parts[parts.length - 1];
            current[lastPart] = {
                $value: allTokens[key],
                $type: this.inferTokenType(key)
            };
        }.bind(this));

        var output = JSON.stringify(w3cTokens, null, 2);
        this.downloadFile(output, 'flavor-tokens.w3c.json', 'application/json');
        return output;
    },

    /**
     * Inferir tipo de token basado en el nombre
     */
    inferTokenType: function(key) {
        if (key.includes('color') || key.includes('primary') || key.includes('secondary') ||
            key.includes('bg') || key.includes('text') || key.includes('border') &&
            !key.includes('radius') && !key.includes('width')) {
            return 'color';
        }
        if (key.includes('spacing') || key.includes('size') || key.includes('radius') ||
            key.includes('width') || key.includes('height')) {
            return 'dimension';
        }
        if (key.includes('font-family')) {
            return 'fontFamily';
        }
        if (key.includes('font-weight')) {
            return 'fontWeight';
        }
        if (key.includes('shadow')) {
            return 'shadow';
        }
        return 'string';
    },

    /**
     * Obtener todos los valores de tokens (base + overrides)
     */
    getAllTokenValues: function() {
        var self = this;
        var allTokens = {};

        // Recorrer todas las categorias de tokens definidos
        Object.keys(this.tokenDefinitions).forEach(function(category) {
            var tokens = self.tokenDefinitions[category].tokens;
            tokens.forEach(function(token) {
                allTokens[token.key] = self.getTokenValue(token.key);
            });
        });

        // Agregar cualquier override adicional
        Object.keys(this.documentTokenOverrides).forEach(function(key) {
            allTokens[key] = self.documentTokenOverrides[key];
        });

        return allTokens;
    },

    /**
     * Descargar archivo
     */
    downloadFile: function(content, filename, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        this.showNotification('Archivo exportado: ' + filename, 'success');
    },

    // ============ INTEGRACION CON INSPECTOR ============

    /**
     * Obtener tokens sugeridos para un tipo de propiedad CSS
     */
    getSuggestedTokens: function(propertyType) {
        var suggestions = [];
        var self = this;

        switch(propertyType) {
            case 'color':
            case 'background-color':
            case 'border-color':
                ['colors', 'backgrounds', 'text', 'borders'].forEach(function(cat) {
                    if (self.tokenDefinitions[cat]) {
                        self.tokenDefinitions[cat].tokens.forEach(function(token) {
                            if (token.type === 'color') {
                                suggestions.push({
                                    key: token.key,
                                    label: token.label,
                                    value: self.getTokenValue(token.key),
                                    category: cat
                                });
                            }
                        });
                    }
                });
                break;

            case 'font-family':
                if (this.tokenDefinitions.typography) {
                    this.tokenDefinitions.typography.tokens.forEach(function(token) {
                        if (token.type === 'font') {
                            suggestions.push({
                                key: token.key,
                                label: token.label,
                                value: self.getTokenValue(token.key),
                                category: 'typography'
                            });
                        }
                    });
                }
                break;

            case 'font-size':
                if (this.tokenDefinitions.typography) {
                    this.tokenDefinitions.typography.tokens.forEach(function(token) {
                        if (token.type === 'size' && token.key.includes('font-size')) {
                            suggestions.push({
                                key: token.key,
                                label: token.label,
                                value: self.getTokenValue(token.key),
                                category: 'typography'
                            });
                        }
                    });
                }
                break;

            case 'padding':
            case 'margin':
            case 'gap':
                if (this.tokenDefinitions.spacing) {
                    this.tokenDefinitions.spacing.tokens.forEach(function(token) {
                        suggestions.push({
                            key: token.key,
                            label: token.label,
                            value: self.getTokenValue(token.key),
                            category: 'spacing'
                        });
                    });
                }
                break;

            case 'border-radius':
                if (this.tokenDefinitions.borders) {
                    this.tokenDefinitions.borders.tokens.forEach(function(token) {
                        if (token.key.includes('radius')) {
                            suggestions.push({
                                key: token.key,
                                label: token.label,
                                value: self.getTokenValue(token.key),
                                category: 'borders'
                            });
                        }
                    });
                }
                break;

            case 'box-shadow':
                if (this.tokenDefinitions.shadows) {
                    this.tokenDefinitions.shadows.tokens.forEach(function(token) {
                        suggestions.push({
                            key: token.key,
                            label: token.label,
                            value: self.getTokenValue(token.key),
                            category: 'shadows'
                        });
                    });
                }
                break;
        }

        return suggestions;
    },

    /**
     * Autocompletar valor con token
     */
    autocompleteWithToken: function(inputValue) {
        if (!inputValue || inputValue.length < 2) return [];

        var query = inputValue.toLowerCase();
        var matches = [];
        var self = this;

        Object.keys(this.tokenDefinitions).forEach(function(category) {
            self.tokenDefinitions[category].tokens.forEach(function(token) {
                if (token.label.toLowerCase().includes(query) ||
                    token.key.toLowerCase().includes(query)) {
                    matches.push({
                        key: token.key,
                        label: token.label,
                        value: self.getTokenValue(token.key),
                        varReference: 'var(' + token.key + ')'
                    });
                }
            });
        });

        return matches.slice(0, 10); // Limitar a 10 sugerencias
    },

    // ============ NOTIFICACIONES ============

    /**
     * Mostrar notificacion
     */
    showNotification: function(message, type) {
        type = type || 'info';

        if (window.VBPToast && typeof window.VBPToast[type] === 'function') {
            window.VBPToast[type](message);
        } else {
            console.log('[VBP Tokens] ' + type.toUpperCase() + ': ' + message);
        }
    }
};
