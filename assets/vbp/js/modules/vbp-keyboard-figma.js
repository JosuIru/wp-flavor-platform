/**
 * Visual Builder Pro - Keyboard Module: Figma
 * Integración con Figma para importar diseños
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardFigma = {
    /**
     * Abrir importador de Figma
     */
    openFigmaImporter: function() {
        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var figmaConfig = config.figmaImport || {};

        // Verificar si Figma está disponible
        if (!figmaConfig.available) {
            this.showNotification('Importación de Figma no disponible', 'warning');
            return;
        }

        // Verificar si está configurado
        if (!figmaConfig.enabled) {
            this.showConfigurationModal();
            return;
        }

        // Abrir modal de importación
        this.showImportModal();
    },

    /**
     * Mostrar modal de configuración de Figma
     */
    showConfigurationModal: function() {
        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var figmaConfig = config.figmaImport || {};
        var strings = figmaConfig.strings || {};

        var modalId = 'vbp-figma-config-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">';
        html += '<div class="vbp-modal" style="background: var(--vbp-surface, #313244); border-radius: 12px; padding: 24px; max-width: 450px; width: 90%;">';
        html += '<div class="vbp-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">';
        html += '<h2 style="margin: 0; font-size: 18px; color: var(--vbp-text, #cdd6f4);">🎨 Importar desde Figma</h2>';
        html += '<button onclick="document.getElementById(\'' + modalId + '\').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--vbp-text-muted);">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body">';
        html += '<div style="text-align: center; padding: 20px;">';
        html += '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" style="margin-bottom: 16px;">';
        html += '<path d="M16 8H32V24H24C19.5817 24 16 20.4183 16 16V8Z" fill="#F24E1E"/>';
        html += '<path d="M32 8H48V16C48 20.4183 44.4183 24 40 24H32V8Z" fill="#FF7262"/>';
        html += '<path d="M16 24H24C28.4183 24 32 27.5817 32 32C32 36.4183 28.4183 40 24 40H16V24Z" fill="#A259FF"/>';
        html += '<path d="M32 24H40C44.4183 24 48 27.5817 48 32C48 36.4183 44.4183 40 40 40C35.5817 40 32 36.4183 32 32V24Z" fill="#1ABCFE"/>';
        html += '<path d="M16 40H24C28.4183 40 32 43.5817 32 48C32 52.4183 28.4183 56 24 56C19.5817 56 16 52.4183 16 48V40Z" fill="#0ACF83"/>';
        html += '</svg>';
        html += '<p style="color: var(--vbp-text-muted); margin-bottom: 20px;">' + (strings.notConfigured || 'Configura tu token de Figma para importar diseños directamente.') + '</p>';
        html += '<a href="' + (config.settingsUrl || '/wp-admin/admin.php?page=flavor-settings') + '" class="vbp-button" style="display: inline-block; padding: 12px 24px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border-radius: 8px; text-decoration: none; font-weight: 600;">' + (strings.configure || 'Configurar Figma') + '</a>';
        html += '</div>';
        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
    },

    /**
     * Mostrar modal de importación
     */
    showImportModal: function() {
        var self = this;
        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var figmaConfig = config.figmaImport || {};
        var strings = figmaConfig.strings || {};

        var modalId = 'vbp-figma-import-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">';
        html += '<div class="vbp-modal" style="background: var(--vbp-surface, #313244); border-radius: 12px; padding: 24px; max-width: 500px; width: 90%;">';
        html += '<div class="vbp-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">';
        html += '<h2 style="margin: 0; font-size: 18px; color: var(--vbp-text, #cdd6f4);">🎨 Importar desde Figma</h2>';
        html += '<button onclick="document.getElementById(\'' + modalId + '\').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--vbp-text-muted);">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body">';
        html += '<div style="margin-bottom: 16px;">';
        html += '<label style="display: block; margin-bottom: 8px; color: var(--vbp-text); font-size: 14px;">URL de Figma</label>';
        html += '<input type="text" id="vbp-figma-url" placeholder="' + (strings.pasteUrl || 'Pega URL de Figma') + '" style="width: 100%; padding: 12px; background: var(--vbp-bg, #1e1e2e); border: 1px solid var(--vbp-border, #45475a); border-radius: 8px; color: var(--vbp-text); font-size: 14px;">';
        html += '<p style="margin-top: 8px; font-size: 12px; color: var(--vbp-text-muted);">Ejemplo: https://www.figma.com/file/xxx/Design?node-id=0:1</p>';
        html += '</div>';
        html += '<div style="margin-bottom: 16px;">';
        html += '<label style="display: flex; align-items: center; gap: 8px; color: var(--vbp-text); font-size: 14px; cursor: pointer;">';
        html += '<input type="checkbox" id="vbp-figma-import-images" checked style="width: 16px; height: 16px;">';
        html += 'Importar imágenes';
        html += '</label>';
        html += '</div>';
        html += '<div id="vbp-figma-preview" style="display: none; margin-bottom: 16px; padding: 16px; background: var(--vbp-bg); border-radius: 8px;"></div>';
        html += '<div id="vbp-figma-error" style="display: none; margin-bottom: 16px; padding: 12px; background: rgba(235, 87, 87, 0.1); border: 1px solid #eb5757; border-radius: 8px; color: #eb5757;"></div>';
        html += '<div style="display: flex; gap: 12px; justify-content: flex-end;">';
        html += '<button onclick="document.getElementById(\'' + modalId + '\').remove()" style="padding: 12px 24px; background: var(--vbp-surface-hover, #45475a); color: var(--vbp-text); border: none; border-radius: 8px; cursor: pointer;">Cancelar</button>';
        html += '<button id="vbp-figma-preview-btn" onclick="window.VBPKeyboardFigma.previewFigma()" style="padding: 12px 24px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Previsualizar</button>';
        html += '<button id="vbp-figma-import-btn" onclick="window.VBPKeyboardFigma.importFigma()" style="display: none; padding: 12px 24px; background: #0ACF83; color: #1e1e2e; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Importar</button>';
        html += '</div>';
        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
    },

    /**
     * Previsualizar estructura de Figma
     */
    previewFigma: function() {
        var url = document.getElementById('vbp-figma-url').value;
        var previewDiv = document.getElementById('vbp-figma-preview');
        var errorDiv = document.getElementById('vbp-figma-error');
        var previewBtn = document.getElementById('vbp-figma-preview-btn');
        var importBtn = document.getElementById('vbp-figma-import-btn');

        if (!url) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Introduce una URL de Figma válida';
            return;
        }

        errorDiv.style.display = 'none';
        previewBtn.textContent = 'Cargando...';
        previewBtn.disabled = true;

        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var figmaConfig = config.figmaImport || {};

        fetch(figmaConfig.endpoints.preview, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.restNonce
            },
            body: JSON.stringify({ url: url })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            previewBtn.textContent = 'Previsualizar';
            previewBtn.disabled = false;

            if (data.success) {
                previewDiv.style.display = 'block';
                previewDiv.innerHTML = '<strong>' + data.name + '</strong><br>' +
                    '<span style="color: var(--vbp-text-muted);">' + data.nodeCount + ' nodos detectados</span>';
                importBtn.style.display = 'block';
            } else {
                errorDiv.style.display = 'block';
                errorDiv.textContent = data.message || 'Error al cargar el diseño';
            }
        })
        .catch(function(error) {
            previewBtn.textContent = 'Previsualizar';
            previewBtn.disabled = false;
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Error de conexión: ' + error.message;
        });
    },

    /**
     * Importar diseño de Figma
     */
    importFigma: function() {
        var url = document.getElementById('vbp-figma-url').value;
        var importImages = document.getElementById('vbp-figma-import-images').checked;
        var errorDiv = document.getElementById('vbp-figma-error');
        var importBtn = document.getElementById('vbp-figma-import-btn');

        errorDiv.style.display = 'none';
        importBtn.textContent = 'Importando...';
        importBtn.disabled = true;

        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var figmaConfig = config.figmaImport || {};
        var strings = figmaConfig.strings || {};

        fetch(figmaConfig.endpoints.import, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.restNonce
            },
            body: JSON.stringify({
                url: url,
                import_images: importImages
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                // Añadir elementos importados al store
                var store = Alpine.store('vbp');
                if (store && data.elements) {
                    data.elements.forEach(function(el) {
                        store.addElement(el);
                    });
                    store.isDirty = true;
                }

                document.getElementById('vbp-figma-import-modal').remove();
                window.VBPKeyboardFigma.showNotification(strings.importSuccess || 'Diseño importado correctamente', 'success');
            } else {
                importBtn.textContent = 'Importar';
                importBtn.disabled = false;
                errorDiv.style.display = 'block';
                errorDiv.textContent = data.message || 'Error al importar';
            }
        })
        .catch(function(error) {
            importBtn.textContent = 'Importar';
            importBtn.disabled = false;
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Error de conexión: ' + error.message;
        });
    },

    /**
     * Mostrar notificación
     */
    showNotification: function(message, type) {
        type = type || 'info';
        if (window.vbpKeyboard && window.vbpKeyboard.showNotification) {
            window.vbpKeyboard.showNotification(message, type);
        } else {
            vbpLog.log('', message);
        }
    }
};
