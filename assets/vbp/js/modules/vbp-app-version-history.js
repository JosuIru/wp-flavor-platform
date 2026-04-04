/**
 * Visual Builder Pro - App Module: Version History
 * Gestión de historial, comparación y restauración de versiones
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppVersionHistory = {
    showVersionHistoryModal: false,
    showVersionDiffModal: false,
    versions: [],
    isLoadingVersions: false,
    selectedVersionA: null,
    selectedVersionB: null,
    versionDiff: null,
    isRestoringVersion: false,
    newVersionLabel: '',

    openVersionHistory: function() {
        this.showVersionHistoryModal = true;
        this.loadVersions();
    },

    loadVersions: function() {
        var self = this;
        this.isLoadingVersions = true;

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.versions = data.versiones;
            } else {
                self.showNotification('Error cargando versiones', 'error');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        })
        .finally(function() {
            self.isLoadingVersions = false;
        });
    },

    createVersionSnapshot: function() {
        var self = this;
        var label = this.newVersionLabel.trim() || '';

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({ label: label })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Versión guardada correctamente', 'success');
                self.newVersionLabel = '';
                self.loadVersions();
            } else {
                throw new Error(data.message || 'Error al crear versión');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    },

    restoreVersion: function(version) {
        var self = this;
        if (!confirm('¿Restaurar a la versión #' + version.version_number + '? Se guardará una copia del estado actual.')) {
            return;
        }

        this.isRestoringVersion = true;

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id + '/restore', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Versión restaurada correctamente', 'success');
                var store = Alpine.store('vbp');
                if (store && data.content) {
                    store.elements = sanitizeElements(data.content);
                }
                self.showVersionHistoryModal = false;
                self.loadVersions();
            } else {
                throw new Error(data.message || 'Error al restaurar versión');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        })
        .finally(function() {
            self.isRestoringVersion = false;
        });
    },

    selectVersionForCompare: function(version, slot) {
        if (slot === 'A') {
            this.selectedVersionA = version;
        } else {
            this.selectedVersionB = version;
        }
    },

    compareVersions: function() {
        var self = this;
        if (!this.selectedVersionA || !this.selectedVersionB) {
            this.showNotification('Selecciona dos versiones para comparar', 'warning');
            return;
        }

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/compare?version_a=' + this.selectedVersionA.id + '&version_b=' + this.selectedVersionB.id, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.versionDiff = data;
                self.showVersionDiffModal = true;
            } else {
                throw new Error(data.message || 'Error al comparar versiones');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    },

    updateVersionLabel: function(version, newLabel) {
        var self = this;

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id + '/label', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            },
            body: JSON.stringify({ label: newLabel })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Etiqueta actualizada', 'success');
                version.label = newLabel;
            } else {
                throw new Error(data.message || 'Error al actualizar etiqueta');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    },

    deleteVersion: function(version) {
        var self = this;
        if (!confirm('¿Eliminar la versión #' + version.version_number + '? Esta acción no se puede deshacer.')) {
            return;
        }

        fetch(VBP_Config.restUrl + 'versions/' + VBP_Config.postId + '/' + version.id, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showNotification('Versión eliminada', 'success');
                self.loadVersions();
            } else {
                throw new Error(data.message || 'Error al eliminar versión');
            }
        })
        .catch(function(error) {
            self.showNotification('Error: ' + error.message, 'error');
        });
    },

    getDiffChangeTypeClass: function(type) {
        var classes = {
            added: 'vbp-diff-added',
            removed: 'vbp-diff-removed',
            modified: 'vbp-diff-modified'
        };
        return classes[type] || '';
    },

    getDiffChangeTypeLabel: function(type) {
        var labels = {
            added: 'Añadido',
            removed: 'Eliminado',
            modified: 'Modificado'
        };
        return labels[type] || type;
    }
};
