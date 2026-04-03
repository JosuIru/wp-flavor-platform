/**
 * Visual Builder Pro - App Module: Import/Export
 * Importación y exportación de diseños
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppImportExport = {
    // Estado
    showExportModal: false,
    importDragOver: false,
    importJsonText: '',

    // ============ IMPORTACIÓN ============

    /**
     * Manejar drop de archivo para importar
     */
    handleImportDrop: function(event) {
        this.importDragOver = false;
        var files = event.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/json') {
            this.readImportFile(files[0]);
        }
    },

    /**
     * Manejar selección de archivo para importar
     */
    handleImportFile: function(event) {
        var files = event.target.files;
        if (files.length > 0) {
            this.readImportFile(files[0]);
        }
    },

    /**
     * Leer archivo JSON importado
     */
    readImportFile: function(file) {
        var self = this;
        var reader = new FileReader();

        reader.onload = function(e) {
            try {
                var data = JSON.parse(e.target.result);
                self.importData(data);
            } catch (error) {
                self.showNotification('Archivo JSON inválido', 'error');
            }
        };

        reader.readAsText(file);
    },

    /**
     * Importar desde texto JSON pegado
     */
    importFromJson: function() {
        if (!this.importJsonText.trim()) return;

        try {
            var data = JSON.parse(this.importJsonText);
            this.importData(data);
        } catch (error) {
            this.showNotification('JSON inválido: ' + error.message, 'error');
        }
    },

    /**
     * Importar datos al editor
     */
    importData: function(data) {
        if (!data.elements && !data.settings) {
            this.showNotification('Formato de datos inválido', 'error');
            return;
        }

        if (!confirm(VBP_Config.strings.confirmImport || '¿Importar este diseño? Se reemplazará el contenido actual.')) {
            return;
        }

        if (data.elements) {
            Alpine.store('vbp').elements = sanitizeElements(data.elements);
        }
        if (data.settings) {
            Alpine.store('vbp').settings = data.settings;
        }

        Alpine.store('vbp').isDirty = true;
        this.showNotification('Diseño importado correctamente', 'success');
        this.showTemplatesModal = false;
        this.importJsonText = '';
    },

    // ============ EXPORTACIÓN ============

    /**
     * Generar JSON de exportación
     */
    getExportJson: function() {
        var data = {
            version: '2.0',
            exported: new Date().toISOString(),
            elements: Alpine.store('vbp').elements,
            settings: Alpine.store('vbp').settings
        };
        return JSON.stringify(data, null, 2);
    },

    /**
     * Exportar como archivo JSON
     */
    exportAsJson: function() {
        var json = this.getExportJson();
        var blob = new Blob([json], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = this.documentTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '-vbp-export.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        this.showNotification('Archivo JSON descargado', 'success');
    },

    /**
     * Copiar JSON al portapapeles
     */
    copyJsonToClipboard: function() {
        var self = this;
        var json = this.getExportJson();

        navigator.clipboard.writeText(json).then(function() {
            self.showNotification('JSON copiado al portapapeles', 'success');
        }).catch(function() {
            // Fallback para navegadores antiguos
            var textarea = document.createElement('textarea');
            textarea.value = json;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            self.showNotification('JSON copiado al portapapeles', 'success');
        });
    },

    /**
     * Exportar como archivo HTML
     */
    exportAsHtml: function() {
        var self = this;

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/export-html', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.html) {
                var blob = new Blob([result.html], { type: 'text/html' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = self.documentTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.html';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                self.showNotification('HTML exportado correctamente', 'success');
            }
        })
        .catch(function(error) {
            self.showNotification('Error exportando HTML', 'error');
        });
    },

    /**
     * Exportar como PDF (requiere backend)
     */
    exportAsPdf: function() {
        var self = this;

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/export-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Error generando PDF');
        })
        .then(function(blob) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = self.documentTitle.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            self.showNotification('PDF exportado correctamente', 'success');
        })
        .catch(function(error) {
            self.showNotification('Error exportando PDF: ' + error.message, 'error');
        });
    }
};
