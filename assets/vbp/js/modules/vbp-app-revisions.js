/**
 * Visual Builder Pro - App Module: Revisions
 * Gestión de revisiones/historial de versiones
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPAppRevisions = {
    // Estado
    revisions: [],
    isLoadingRevisions: false,
    isRestoringRevision: false,
    showRevisionsModal: false,

    // Estado de comparación
    showCompareModal: false,
    isLoadingCompare: false,
    compareRevision1: null,
    compareRevision2: null,
    compareData1: null,
    compareData2: null,
    compareDiff: [],
    selectedForCompare: [],

    /**
     * Abrir modal de revisiones
     */
    openRevisionsModal: function() {
        this.showRevisionsModal = true;
        this.loadRevisions();
    },

    /**
     * Cargar lista de revisiones desde la API
     */
    loadRevisions: function() {
        var self = this;
        this.isLoadingRevisions = true;
        this.revisions = [];

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (Array.isArray(data)) {
                self.revisions = data.map(function(rev, index) {
                    return {
                        id: rev.id,
                        date: rev.date,
                        author: rev.author || 'Usuario',
                        title: rev.title || '',
                        isCurrent: index === 0
                    };
                });
            }
        })
        .catch(function(error) {
            vbpLog.error('Error cargando revisiones:', error);
            self.showNotification('Error cargando revisiones', 'error');
        })
        .finally(function() {
            self.isLoadingRevisions = false;
        });
    },

    /**
     * Formatear fecha de revisión en formato relativo
     */
    formatRevisionDate: function(dateString) {
        if (!dateString) return '';

        var date = new Date(dateString);
        var now = new Date();
        var diffMs = now - date;
        var diffMins = Math.floor(diffMs / 60000);
        var diffHours = Math.floor(diffMs / 3600000);
        var diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Ahora mismo';
        } else if (diffMins < 60) {
            return 'Hace ' + diffMins + ' minuto' + (diffMins === 1 ? '' : 's');
        } else if (diffHours < 24) {
            return 'Hace ' + diffHours + ' hora' + (diffHours === 1 ? '' : 's');
        } else if (diffDays < 7) {
            return 'Hace ' + diffDays + ' día' + (diffDays === 1 ? '' : 's');
        } else {
            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    },

    /**
     * Restaurar una revisión específica
     */
    restoreRevision: function(revision) {
        if (!confirm(VBP_Config.strings.confirmRestoreRevision || '¿Restaurar esta versión? Se perderán los cambios no guardados.')) {
            return;
        }

        var self = this;
        this.isRestoringRevision = true;

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions/' + revision.id + '/restore', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                self.showNotification('Revisión restaurada correctamente', 'success');
                self.showRevisionsModal = false;
                // Recargar el documento
                self.loadDocument();
            } else {
                throw new Error(result.message || 'Error desconocido');
            }
        })
        .catch(function(error) {
            self.showNotification('Error restaurando revisión: ' + error.message, 'error');
        })
        .finally(function() {
            self.isRestoringRevision = false;
        });
    },

    /**
     * Previsualizar una revisión (sin aplicar)
     */
    previewRevision: function(revision) {
        var self = this;

        fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions/' + revision.id, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.elements) {
                // Mostrar preview en modal o panel
                self.showNotification('Vista previa cargada - ' + self.formatRevisionDate(revision.date), 'info');
            }
        })
        .catch(function(error) {
            self.showNotification('Error cargando vista previa', 'error');
        });
    },

    /**
     * Seleccionar revisión para comparar
     */
    toggleSelectForCompare: function(revision) {
        var index = this.selectedForCompare.findIndex(function(r) {
            return r.id === revision.id;
        });

        if (index > -1) {
            this.selectedForCompare.splice(index, 1);
        } else if (this.selectedForCompare.length < 2) {
            this.selectedForCompare.push(revision);
        } else {
            this.showNotification('Solo puedes seleccionar 2 revisiones para comparar', 'warning');
        }
    },

    /**
     * Verificar si una revisión está seleccionada para comparar
     */
    isSelectedForCompare: function(revision) {
        return this.selectedForCompare.some(function(r) {
            return r.id === revision.id;
        });
    },

    /**
     * Iniciar comparación de revisiones seleccionadas
     */
    startCompare: function() {
        if (this.selectedForCompare.length !== 2) {
            this.showNotification('Selecciona exactamente 2 revisiones para comparar', 'warning');
            return;
        }

        // Ordenar por fecha (más antigua primero)
        var sorted = this.selectedForCompare.slice().sort(function(a, b) {
            return new Date(a.date) - new Date(b.date);
        });

        this.compareRevisions(sorted[0], sorted[1]);
    },

    /**
     * Comparar dos revisiones
     */
    compareRevisions: function(revision1, revision2) {
        var self = this;
        this.compareRevision1 = revision1;
        this.compareRevision2 = revision2;
        this.isLoadingCompare = true;
        this.showCompareModal = true;
        this.compareDiff = [];

        // Cargar ambas revisiones en paralelo
        Promise.all([
            this.fetchRevisionData(revision1.id),
            this.fetchRevisionData(revision2.id)
        ])
        .then(function(results) {
            self.compareData1 = results[0];
            self.compareData2 = results[1];
            self.compareDiff = self.calculateDiff(results[0], results[1]);
        })
        .catch(function(error) {
            vbpLog.error('Error comparando revisiones:', error);
            self.showNotification('Error al cargar revisiones para comparar', 'error');
        })
        .finally(function() {
            self.isLoadingCompare = false;
        });
    },

    /**
     * Obtener datos de una revisión específica
     */
    fetchRevisionData: function(revisionId) {
        return fetch(VBP_Config.restUrl + 'documents/' + VBP_Config.postId + '/revisions/' + revisionId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': VBP_Config.restNonce
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            return {
                elements: data.elements || [],
                settings: data.settings || {}
            };
        });
    },

    /**
     * Calcular diferencias entre dos versiones
     */
    calculateDiff: function(data1, data2) {
        var diff = [];
        var elements1 = this.flattenElements(data1.elements);
        var elements2 = this.flattenElements(data2.elements);

        var ids1 = Object.keys(elements1);
        var ids2 = Object.keys(elements2);
        var allIds = Array.from(new Set(ids1.concat(ids2)));

        allIds.forEach(function(id) {
            var el1 = elements1[id];
            var el2 = elements2[id];

            if (!el1 && el2) {
                // Elemento añadido
                diff.push({
                    type: 'added',
                    element: el2,
                    elementId: id,
                    description: 'Elemento añadido: ' + (el2.name || el2.type)
                });
            } else if (el1 && !el2) {
                // Elemento eliminado
                diff.push({
                    type: 'removed',
                    element: el1,
                    elementId: id,
                    description: 'Elemento eliminado: ' + (el1.name || el1.type)
                });
            } else if (el1 && el2) {
                // Comparar cambios
                var changes = this.compareElements(el1, el2);
                if (changes.length > 0) {
                    diff.push({
                        type: 'modified',
                        element: el2,
                        elementId: id,
                        changes: changes,
                        description: 'Modificado: ' + (el2.name || el2.type)
                    });
                }
            }
        }, this);

        return diff;
    },

    /**
     * Aplanar árbol de elementos a un objeto plano por ID
     */
    flattenElements: function(elements, result) {
        result = result || {};
        var self = this;

        if (!Array.isArray(elements)) return result;

        elements.forEach(function(el) {
            if (el && el.id) {
                result[el.id] = el;
                if (el.children && el.children.length > 0) {
                    self.flattenElements(el.children, result);
                }
            }
        });

        return result;
    },

    /**
     * Comparar dos elementos y retornar lista de cambios
     */
    compareElements: function(el1, el2) {
        var changes = [];
        var self = this;

        // Comparar propiedades principales
        var propsToCompare = ['type', 'name', 'visible', 'locked'];

        propsToCompare.forEach(function(prop) {
            if (el1[prop] !== el2[prop]) {
                changes.push({
                    property: prop,
                    oldValue: el1[prop],
                    newValue: el2[prop]
                });
            }
        });

        // Comparar datos (estilos, contenido, etc.)
        if (el1.data || el2.data) {
            var dataChanges = this.compareObjects(el1.data || {}, el2.data || {}, 'data');
            changes = changes.concat(dataChanges);
        }

        // Comparar estilos
        if (el1.styles || el2.styles) {
            var styleChanges = this.compareObjects(el1.styles || {}, el2.styles || {}, 'styles');
            changes = changes.concat(styleChanges);
        }

        return changes;
    },

    /**
     * Comparar dos objetos y retornar diferencias
     */
    compareObjects: function(obj1, obj2, prefix) {
        var changes = [];
        var allKeys = Array.from(new Set(Object.keys(obj1).concat(Object.keys(obj2))));

        allKeys.forEach(function(key) {
            var val1 = obj1[key];
            var val2 = obj2[key];

            // Ignorar funciones y referencias circulares
            if (typeof val1 === 'function' || typeof val2 === 'function') return;

            var path = prefix + '.' + key;

            if (val1 === undefined && val2 !== undefined) {
                changes.push({ property: path, oldValue: null, newValue: val2 });
            } else if (val1 !== undefined && val2 === undefined) {
                changes.push({ property: path, oldValue: val1, newValue: null });
            } else if (typeof val1 === 'object' && typeof val2 === 'object' && val1 !== null && val2 !== null) {
                // Comparar como JSON para simplicidad
                if (JSON.stringify(val1) !== JSON.stringify(val2)) {
                    changes.push({ property: path, oldValue: val1, newValue: val2 });
                }
            } else if (val1 !== val2) {
                changes.push({ property: path, oldValue: val1, newValue: val2 });
            }
        });

        return changes;
    },

    /**
     * Obtener clase CSS para tipo de cambio
     */
    getDiffTypeClass: function(type) {
        switch (type) {
            case 'added': return 'vbp-diff-added';
            case 'removed': return 'vbp-diff-removed';
            case 'modified': return 'vbp-diff-modified';
            default: return '';
        }
    },

    /**
     * Obtener icono para tipo de cambio
     */
    getDiffTypeIcon: function(type) {
        switch (type) {
            case 'added': return '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/></svg>';
            case 'removed': return '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z"/></svg>';
            case 'modified': return '<svg class="vbp-diff-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>';
            default: return '';
        }
    },

    /**
     * Formatear valor para mostrar en diff
     */
    formatDiffValue: function(value) {
        if (value === null || value === undefined) {
            return '<em class="vbp-diff-null">(vacío)</em>';
        }
        if (typeof value === 'object') {
            return '<code>' + JSON.stringify(value).substring(0, 50) + (JSON.stringify(value).length > 50 ? '...' : '') + '</code>';
        }
        if (typeof value === 'boolean') {
            return value ? '<span class="vbp-diff-true">Sí</span>' : '<span class="vbp-diff-false">No</span>';
        }
        return String(value);
    },

    /**
     * Cerrar modal de comparación
     */
    closeCompareModal: function() {
        this.showCompareModal = false;
        this.compareRevision1 = null;
        this.compareRevision2 = null;
        this.compareData1 = null;
        this.compareData2 = null;
        this.compareDiff = [];
        this.selectedForCompare = [];
    },

    /**
     * Obtener resumen de cambios
     */
    getComparisonSummary: function() {
        var added = this.compareDiff.filter(function(d) { return d.type === 'added'; }).length;
        var removed = this.compareDiff.filter(function(d) { return d.type === 'removed'; }).length;
        var modified = this.compareDiff.filter(function(d) { return d.type === 'modified'; }).length;

        return {
            added: added,
            removed: removed,
            modified: modified,
            total: this.compareDiff.length
        };
    }
};
