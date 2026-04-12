/**
 * Visual Builder Pro - Layers Panel
 * Panel de capas
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

/**
 * Componente Panel de Capas
 * Definido como función global para que Alpine lo encuentre
 */
function vbpLayersComponent() {
    return {
        editingId: null,
        editingName: '',
        dragOverId: null,
        draggedElement: null,
        draggedIndex: null,
        sortableInstance: null,

        // Búsqueda y filtrado
        searchQuery: '',
        filterType: '',
        showFilters: false,

        init() {
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        /**
         * Obtiene los elementos del store
         */
        get elements() {
            const store = Alpine.store('vbp');
            return store && store.elements ? store.elements : [];
        },

        /**
         * Obtiene los elementos seleccionados
         */
        get selectedElements() {
            const store = Alpine.store('vbp');
            return store && store.selection && store.selection.elementIds ? store.selection.elementIds : [];
        },

        /**
         * Obtiene los elementos filtrados según búsqueda y tipo
         */
        get filteredElements() {
            let resultado = this.elements;

            // Filtrar por texto de búsqueda
            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const busqueda = this.searchQuery.toLowerCase().trim();
                resultado = resultado.filter(elemento => {
                    const nombreElemento = (elemento.name || elemento.type || '').toLowerCase();
                    const tipoElemento = (elemento.type || '').toLowerCase();
                    const idElemento = (elemento.id || '').toLowerCase();
                    return nombreElemento.includes(busqueda) ||
                           tipoElemento.includes(busqueda) ||
                           idElemento.includes(busqueda);
                });
            }

            // Filtrar por tipo
            if (this.filterType && this.filterType !== '') {
                resultado = resultado.filter(elemento => elemento.type === this.filterType);
            }

            return resultado;
        },

        /**
         * Obtiene los tipos de elementos únicos para el filtro
         */
        get availableTypes() {
            const tiposUnicos = [...new Set(this.elements.map(elemento => elemento.type))];
            return tiposUnicos.sort();
        },

        /**
         * Indica si hay filtros activos
         */
        get hasActiveFilters() {
            return (this.searchQuery && this.searchQuery.trim() !== '') ||
                   (this.filterType && this.filterType !== '');
        },

        /**
         * Cuenta de elementos filtrados
         */
        get filterCount() {
            return this.filteredElements.length;
        },

        /**
         * Limpia todos los filtros
         */
        clearFilters() {
            this.searchQuery = '';
            this.filterType = '';
        },

        /**
         * Toggle del panel de filtros
         */
        toggleFilters() {
            this.showFilters = !this.showFilters;
        },

        /**
         * Filtra capas por texto (método para compatibilidad)
         */
        filterLayers(query) {
            this.searchQuery = query;
        },

        /**
         * Filtra capas por tipo
         */
        filterByType(type) {
            this.filterType = type;
        },

        /**
         * Resalta el texto que coincide con la búsqueda
         */
        highlightMatch(text) {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                return text;
            }
            const busqueda = this.searchQuery.trim();
            const regex = new RegExp(`(${busqueda})`, 'gi');
            return text.replace(regex, '<mark class="vbp-search-highlight">$1</mark>');
        },

        /**
         * Inicializa SortableJS para reordenar capas
         */
        initSortable() {
            const lista = this.$refs.layersList;
            if (!lista || typeof Sortable === 'undefined') return;

            this.sortableInstance = new Sortable(lista, {
                animation: 150,
                handle: '.vbp-layer-item',
                ghostClass: 'vbp-layer-ghost',
                chosenClass: 'vbp-layer-chosen',
                filter: '.vbp-layer-item.locked',
                preventOnFilter: true,

                onEnd: (evt) => {
                    if (evt.oldIndex !== evt.newIndex) {
                        Alpine.store('vbp').moveElement(evt.oldIndex, evt.newIndex);
                    }
                }
            });
        },

        /**
         * Verifica si un elemento está seleccionado
         */
        isSelected(elementId) {
            return this.selectedElements.includes(elementId);
        },

        /**
         * Selecciona un elemento
         */
        selectElement(element, event) {
            if (element.locked) return;

            const multiSelect = event.ctrlKey || event.metaKey || event.shiftKey;

            if (multiSelect) {
                Alpine.store('vbp').toggleSelection(element.id);
            } else {
                Alpine.store('vbp').setSelection([element.id]);
            }
        },

        /**
         * Inicia la edición del nombre
         */
        renameElement(element) {
            if (element.locked) return;

            this.editingId = element.id;
            this.editingName = element.name || element.type;

            this.$nextTick(() => {
                const input = this.$el.querySelector('.vbp-layer-name-input');
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        },

        /**
         * Guarda el nombre editado
         */
        saveElementName(element) {
            if (this.editingName.trim()) {
                Alpine.store('vbp').updateElement(element.id, {
                    name: this.editingName.trim()
                });
            }
            this.cancelEdit();
        },

        /**
         * Cancela la edición
         */
        cancelEdit() {
            this.editingId = null;
            this.editingName = '';
        },

        /**
         * Toggle visibilidad
         */
        toggleVisibility(element) {
            Alpine.store('vbp').updateElement(element.id, {
                visible: element.visible === false
            });
        },

        /**
         * Toggle bloqueo
         */
        toggleLock(element) {
            Alpine.store('vbp').updateElement(element.id, {
                locked: !element.locked
            });
        },

        /**
         * Selecciona todos los elementos
         */
        selectAll() {
            const todosIds = this.elements.map(e => e.id);
            Alpine.store('vbp').setSelection(todosIds);
        },

        /**
         * Elimina los elementos seleccionados
         */
        deleteSelected() {
            if (this.selectedElements.length === 0) return;

            if (confirm(VBP_Config.strings.deleteConfirm)) {
                this.selectedElements.forEach(id => {
                    Alpine.store('vbp').removeElement(id);
                });
                Alpine.store('vbp').clearSelection();
            }
        },

        /**
         * Duplica los elementos seleccionados
         */
        duplicateSelected() {
            if (this.selectedElements.length === 0) return;

            const nuevosIds = [];
            this.selectedElements.forEach(id => {
                const duplicado = Alpine.store('vbp').duplicateElement(id);
                if (duplicado) nuevosIds.push(duplicado.id);
            });

            Alpine.store('vbp').setSelection(nuevosIds);
        },

        /**
         * Obtiene el icono del tipo de elemento
         */
        getTypeIcon(type) {
            const iconos = {
                'hero': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
                'features': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
                'testimonials': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
                'pricing': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 100 4h4a2 2 0 110 4H8"/></svg>',
                'text': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4,7 4,4 20,4 20,7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
                'heading': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>',
                'image': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'button': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>',
                'container': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
                'columns': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>',
                'divider': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/></svg>',
                'spacer': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
                'gallery': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>',
                'video': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>',
                'mapa': '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1,6 1,22 8,18 16,22 23,18 23,2 16,6 8,2"/></svg>',
            };

            return iconos[type] || '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>';
        },

        /**
         * Drag & Drop handlers
         */
        handleDragStart(event, element, index) {
            if (element.locked) {
                event.preventDefault();
                return;
            }

            this.draggedElement = element;
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', element.id);

            event.target.classList.add('dragging');
        },

        handleDragOver(event, element, index) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            if (this.draggedElement && this.draggedElement.id !== element.id) {
                this.dragOverId = element.id;
            }
        },

        handleDragLeave(event) {
            this.dragOverId = null;
        },

        handleDrop(event, element, index) {
            event.preventDefault();
            this.dragOverId = null;

            if (this.draggedElement && this.draggedIndex !== null && this.draggedIndex !== index) {
                Alpine.store('vbp').moveElement(this.draggedIndex, index);
            }
        },

        handleDragEnd(event) {
            event.target.classList.remove('dragging');
            this.draggedElement = null;
            this.draggedIndex = null;
            this.dragOverId = null;
        },

        /**
         * Destruye el Sortable al desmontar
         */
        destroy() {
            if (this.sortableInstance) {
                this.sortableInstance.destroy();
            }
        }
    };
}

// Exportar a window para acceso global
window.vbpLayersComponent = vbpLayersComponent;

/**
 * Registrar componente en Alpine
 */
function registerLayersComponent() {
    if (typeof Alpine === 'undefined') return false;
    Alpine.data('vbpLayersComponent', vbpLayersComponent);
    return true;
}

// Registrar inmediatamente si Alpine ya existe
if (typeof Alpine !== 'undefined') {
    registerLayersComponent();
}

// También escuchar el evento por si Alpine se carga después
document.addEventListener('alpine:init', function() {
    registerLayersComponent();
});
