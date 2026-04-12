/**
 * Visual Builder Pro - Keyboard Tools Module
 * Herramientas: grid, guías, rulers, bookmarks, favoritos, etc.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardTools = {
    // Estado de herramientas
    snapToGridEnabled: false,
    smartGuidesEnabled: false,
    measureToolEnabled: false,
    panModeEnabled: false,
    rulersVisible: false,
    gridSize: 8,
    bookmarks: {},
    favorites: [],

    /**
     * Toggle grid
     */
    toggleGrid: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        var gridVisible = canvas.classList.toggle('vbp-show-grid');
        localStorage.setItem('vbp_grid_visible', gridVisible ? 'true' : 'false');

        if (gridVisible) {
            this.updateGridStyles();
        }

        window.vbpKeyboard.showNotification(gridVisible ? '📐 Grid visible' : '📐 Grid oculto');
    },

    /**
     * Actualizar estilos del grid
     */
    updateGridStyles: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        canvas.style.setProperty('--vbp-grid-size', this.gridSize + 'px');
    },

    /**
     * Toggle snap to grid
     */
    toggleSnapToGrid: function() {
        this.snapToGridEnabled = !this.snapToGridEnabled;
        localStorage.setItem('vbp_snap_to_grid', this.snapToGridEnabled ? 'true' : 'false');
        window.vbpKeyboard.showNotification(this.snapToGridEnabled ? '🧲 Snap activado' : '🧲 Snap desactivado');
    },

    /**
     * Snap value to grid
     */
    snapToGrid: function(value) {
        if (!this.snapToGridEnabled) return value;
        return Math.round(value / this.gridSize) * this.gridSize;
    },

    /**
     * Toggle guías
     */
    toggleGuides: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        var guidesVisible = canvas.classList.toggle('vbp-show-guides');
        window.vbpKeyboard.showNotification(guidesVisible ? '📏 Guías visibles' : '📏 Guías ocultas');
    },

    /**
     * Toggle rulers
     */
    toggleRulers: function() {
        this.rulersVisible = !this.rulersVisible;

        if (this.rulersVisible) {
            this.showRulers();
        } else {
            this.hideRulers();
        }

        window.vbpKeyboard.showNotification(this.rulersVisible ? '📏 Reglas visibles' : '📏 Reglas ocultas');
    },

    /**
     * Mostrar rulers
     */
    showRulers: function() {
        var container = document.querySelector('.vbp-editor-content');
        if (!container) return;

        var existentes = container.querySelectorAll('.vbp-ruler');
        existentes.forEach(function(r) { r.remove(); });

        // Ruler horizontal
        var rulerH = document.createElement('div');
        rulerH.className = 'vbp-ruler vbp-ruler-horizontal';
        rulerH.style.cssText = 'position: absolute; top: 0; left: 32px; right: 0; height: 24px; background: var(--vbp-surface); border-bottom: 1px solid var(--vbp-border); z-index: 100; display: flex; align-items: flex-end; overflow: hidden;';

        for (var i = 0; i <= 2000; i += 50) {
            var mark = document.createElement('div');
            mark.style.cssText = 'position: absolute; left: ' + i + 'px; height: ' + (i % 100 === 0 ? '16px' : '8px') + '; width: 1px; background: var(--vbp-text-muted);';
            if (i % 100 === 0) {
                var label = document.createElement('span');
                label.textContent = i;
                label.style.cssText = 'position: absolute; left: ' + (i + 4) + 'px; top: 2px; font-size: 9px; color: var(--vbp-text-muted);';
                rulerH.appendChild(label);
            }
            rulerH.appendChild(mark);
        }

        // Ruler vertical
        var rulerV = document.createElement('div');
        rulerV.className = 'vbp-ruler vbp-ruler-vertical';
        rulerV.style.cssText = 'position: absolute; top: 24px; left: 0; bottom: 0; width: 24px; background: var(--vbp-surface); border-right: 1px solid var(--vbp-border); z-index: 100; overflow: hidden;';

        for (var j = 0; j <= 2000; j += 50) {
            var markV = document.createElement('div');
            markV.style.cssText = 'position: absolute; top: ' + j + 'px; width: ' + (j % 100 === 0 ? '16px' : '8px') + '; height: 1px; background: var(--vbp-text-muted);';
            if (j % 100 === 0) {
                var labelV = document.createElement('span');
                labelV.textContent = j;
                labelV.style.cssText = 'position: absolute; top: ' + (j + 4) + 'px; left: 2px; font-size: 9px; color: var(--vbp-text-muted); writing-mode: vertical-lr;';
                rulerV.appendChild(labelV);
            }
            rulerV.appendChild(markV);
        }

        container.appendChild(rulerH);
        container.appendChild(rulerV);
    },

    /**
     * Ocultar rulers
     */
    hideRulers: function() {
        var rulers = document.querySelectorAll('.vbp-ruler');
        rulers.forEach(function(r) { r.remove(); });
    },

    /**
     * Abrir configuración de grid
     */
    openGridSettings: function() {
        var self = this;
        var modalId = 'vbp-grid-settings-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
        html += '<div class="vbp-modal" style="max-width: 400px;">';
        html += '<div class="vbp-modal-header">';
        html += '<h2>📐 Configuración de Grid</h2>';
        html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body">';

        html += '<div style="margin-bottom: 16px;">';
        html += '<label style="display: block; margin-bottom: 8px; font-weight: 500;">Tamaño del grid</label>';
        html += '<select id="grid-size-select" style="width: 100%; padding: 8px; border: 1px solid var(--vbp-border); border-radius: 6px; background: var(--vbp-surface);">';
        [4, 8, 12, 16, 20, 24, 32].forEach(function(size) {
            html += '<option value="' + size + '"' + (size === self.gridSize ? ' selected' : '') + '>' + size + 'px</option>';
        });
        html += '</select>';
        html += '</div>';

        html += '<div style="display: flex; gap: 12px;">';
        html += '<button onclick="window.VBPKeyboardTools.saveGridSettings()" style="flex: 1; padding: 10px; background: var(--vbp-primary); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Guardar</button>';
        html += '<button onclick="document.getElementById(\'' + modalId + '\').remove()" style="flex: 1; padding: 10px; background: var(--vbp-surface); border: 1px solid var(--vbp-border); border-radius: 6px; cursor: pointer;">Cancelar</button>';
        html += '</div>';

        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
    },

    /**
     * Guardar configuración de grid
     */
    saveGridSettings: function() {
        var select = document.getElementById('grid-size-select');
        if (select) {
            this.gridSize = parseInt(select.value, 10);
            localStorage.setItem('vbp_grid_size', this.gridSize);
            this.updateGridStyles();
            window.vbpKeyboard.showNotification('📐 Grid: ' + this.gridSize + 'px');
        }
        var modal = document.getElementById('vbp-grid-settings-modal');
        if (modal) modal.remove();
    },

    /**
     * Toggle pan mode
     */
    togglePanMode: function() {
        this.panModeEnabled = !this.panModeEnabled;
        var canvas = document.querySelector('.vbp-canvas');

        if (canvas) {
            if (this.panModeEnabled) {
                canvas.classList.add('vbp-pan-mode');
                canvas.style.cursor = 'grab';
                this.initPanListeners();
            } else {
                canvas.classList.remove('vbp-pan-mode');
                canvas.style.cursor = '';
            }
        }

        window.vbpKeyboard.showNotification(this.panModeEnabled ? '✋ Modo pan activado' : '✋ Modo pan desactivado');
    },

    /**
     * Inicializar listeners de pan
     */
    initPanListeners: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        var isDragging = false;
        var startX, startY, scrollLeft, scrollTop;

        canvas.addEventListener('mousedown', function(e) {
            if (!window.VBPKeyboardTools.panModeEnabled) return;
            isDragging = true;
            canvas.style.cursor = 'grabbing';
            startX = e.pageX - canvas.offsetLeft;
            startY = e.pageY - canvas.offsetTop;
            scrollLeft = canvas.scrollLeft;
            scrollTop = canvas.scrollTop;
        });

        canvas.addEventListener('mouseup', function() {
            isDragging = false;
            if (window.VBPKeyboardTools.panModeEnabled) {
                canvas.style.cursor = 'grab';
            }
        });

        canvas.addEventListener('mousemove', function(e) {
            if (!isDragging || !window.VBPKeyboardTools.panModeEnabled) return;
            e.preventDefault();
            var x = e.pageX - canvas.offsetLeft;
            var y = e.pageY - canvas.offsetTop;
            var walkX = (x - startX) * 2;
            var walkY = (y - startY) * 2;
            canvas.scrollLeft = scrollLeft - walkX;
            canvas.scrollTop = scrollTop - walkY;
        });
    },

    /**
     * Set bookmark
     */
    setBookmark: function(index) {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para marcar', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para marcar', 'warning');
            return;
        }

        this.bookmarks[index] = {
            elementId: store.selection.elementIds[0],
            timestamp: Date.now()
        };

        localStorage.setItem('vbp_bookmarks', JSON.stringify(this.bookmarks));
        window.vbpKeyboard.showNotification('🔖 Bookmark ' + index + ' guardado');
    },

    /**
     * Go to bookmark
     */
    goToBookmark: function(index) {
        var bookmark = this.bookmarks[index];

        if (!bookmark) {
            window.vbpKeyboard.showNotification('Bookmark ' + index + ' no existe', 'warning');
            return;
        }

        var store = Alpine.store('vbp');
        var elemento = store.getElement(bookmark.elementId);

        if (!elemento) {
            window.vbpKeyboard.showNotification('Elemento del bookmark no encontrado', 'warning');
            return;
        }

        store.setSelection([bookmark.elementId]);

        var elementoCanvas = document.querySelector('[data-element-id="' + bookmark.elementId + '"]');
        if (elementoCanvas) {
            elementoCanvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        window.vbpKeyboard.showNotification('🔖 Ir a bookmark ' + index);
    },

    /**
     * Quick rename
     */
    quickRename: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para renombrar', 'warning');
            return;
        }

        if (store.selection.elementIds.length !== 1) {
            window.vbpKeyboard.showNotification('Selecciona un elemento para renombrar', 'warning');
            return;
        }

        var elementId = store.selection.elementIds[0];
        var element = store.getElement(elementId);

        if (!element) return;

        var nuevoNombre = prompt('Nuevo nombre:', element.name || element.type);

        if (nuevoNombre && nuevoNombre.trim()) {
            store.updateElement(elementId, { name: nuevoNombre.trim() });
            store.isDirty = true;
            window.vbpKeyboard.showNotification('✏️ Renombrado a: ' + nuevoNombre.trim());
        }
    },

    /**
     * Toggle aspect ratio lock
     */
    toggleAspectRatioLock: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un elemento', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona un elemento', 'warning');
            return;
        }

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var locked = element.aspectRatioLocked || false;
                store.updateElement(id, { aspectRatioLocked: !locked });
            }
        });

        window.vbpKeyboard.showNotification('🔒 Aspect ratio ' + (element.aspectRatioLocked ? 'desbloqueado' : 'bloqueado'));
    },

    /**
     * Toggle colapsar
     */
    toggleCollapse: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds || store.selection.elementIds.length === 0) return;

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var collapsed = element.collapsed || false;
                store.updateElement(id, { collapsed: !collapsed });
            }
        });

        window.vbpKeyboard.showNotification('📁 Toggle colapsar');
    },

    /**
     * Toggle constraint
     */
    toggleConstraint: function(side) {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un elemento', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona un elemento', 'warning');
            return;
        }

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var constraints = element.constraints || {};
                constraints[side] = !constraints[side];
                store.updateElement(id, { constraints: constraints });
            }
        });

        window.vbpKeyboard.showNotification('📌 Constraint ' + side + ' toggled');
    },

    /**
     * Guardar como favorito
     */
    saveAsFavorite: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona elementos para guardar como favorito', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona elementos para guardar como favorito', 'warning');
            return;
        }

        var nombre = prompt('Nombre del favorito:', 'Mi Favorito');
        if (!nombre) return;

        var elementos = store.selection.elementIds.map(function(id) {
            return store.getElement(id);
        }).filter(function(el) { return el !== null; });

        this.favorites.push({
            name: nombre,
            elements: JSON.parse(JSON.stringify(elementos)),
            timestamp: Date.now()
        });

        localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));
        window.vbpKeyboard.showNotification('⭐ Favorito guardado: ' + nombre);
    },

    /**
     * Abrir panel de favoritos
     */
    openFavoritesPanel: function() {
        var self = this;
        var modalId = 'vbp-favorites-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var storedFavorites = localStorage.getItem('vbp_favorites');
        if (storedFavorites) {
            this.favorites = JSON.parse(storedFavorites);
        }

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
        html += '<div class="vbp-modal" style="max-width: 500px;">';
        html += '<div class="vbp-modal-header">';
        html += '<h2>⭐ Mis Favoritos</h2>';
        html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body" style="max-height: 400px; overflow-y: auto;">';

        if (this.favorites.length === 0) {
            html += '<p style="text-align: center; color: var(--vbp-text-muted); padding: 40px;">No hay favoritos guardados.<br>Selecciona elementos y presiona Ctrl+Alt+K para guardar.</p>';
        } else {
            html += '<div style="display: flex; flex-direction: column; gap: 8px;">';
            this.favorites.forEach(function(fav, index) {
                html += '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--vbp-surface); border: 1px solid var(--vbp-border); border-radius: 8px;">';
                html += '<span style="flex: 1; font-weight: 500;">' + fav.name + '</span>';
                html += '<span style="font-size: 12px; color: var(--vbp-text-muted);">' + fav.elements.length + ' elementos</span>';
                html += '<button onclick="window.VBPKeyboardTools.insertFavorite(' + index + ')" style="padding: 6px 12px; background: var(--vbp-primary); color: #1e1e2e; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Insertar</button>';
                html += '<button onclick="window.VBPKeyboardTools.deleteFavorite(' + index + ')" style="padding: 6px 10px; background: transparent; color: var(--vbp-error, #f38ba8); border: 1px solid var(--vbp-error, #f38ba8); border-radius: 4px; cursor: pointer; font-size: 12px;">×</button>';
                html += '</div>';
            });
            html += '</div>';
        }

        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
    },

    /**
     * Insertar favorito
     */
    insertFavorite: function(index) {
        var store = Alpine.store('vbp');
        var favorito = this.favorites[index];

        if (!favorito) return;

        store.saveToHistory();

        var newIds = [];
        favorito.elements.forEach(function(elementData) {
            var newElement = JSON.parse(JSON.stringify(elementData));
            newElement.id = 'el_' + Math.random().toString(36).substr(2, 9);
            store.elements.push(newElement);
            newIds.push(newElement.id);
        });

        store.isDirty = true;
        store.setSelection(newIds);

        var modal = document.getElementById('vbp-favorites-modal');
        if (modal) modal.remove();

        window.vbpKeyboard.showNotification('⭐ Insertado: ' + favorito.name);
    },

    /**
     * Eliminar favorito
     */
    deleteFavorite: function(index) {
        if (!confirm('¿Eliminar este favorito?')) return;

        this.favorites.splice(index, 1);
        localStorage.setItem('vbp_favorites', JSON.stringify(this.favorites));

        var modal = document.getElementById('vbp-favorites-modal');
        if (modal) modal.remove();
        this.openFavoritesPanel();

        window.vbpKeyboard.showNotification('🗑️ Favorito eliminado');
    },

    /**
     * Abrir diálogo de búsqueda
     */
    openFindDialog: function() {
        var self = this;
        var modalId = 'vbp-find-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
        html += '<div class="vbp-modal" style="max-width: 450px;">';
        html += '<div class="vbp-modal-header">';
        html += '<h2>🔍 Buscar Elementos</h2>';
        html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body">';

        html += '<input type="text" id="find-input" placeholder="Buscar por nombre o tipo..." style="width: 100%; padding: 12px; border: 1px solid var(--vbp-border); border-radius: 8px; background: var(--vbp-surface); margin-bottom: 16px;" autofocus>';

        html += '<div id="find-results" style="max-height: 300px; overflow-y: auto;"></div>';

        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);

        var input = document.getElementById('find-input');
        var results = document.getElementById('find-results');

        input.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            var store = Alpine.store('vbp');

            if (query === '') {
                results.innerHTML = '';
                return;
            }

            var encontrados = store.elements.filter(function(el) {
                var nombre = (el.name || '').toLowerCase();
                var tipo = (el.type || '').toLowerCase();
                return nombre.includes(query) || tipo.includes(query);
            });

            if (encontrados.length === 0) {
                results.innerHTML = '<p style="text-align: center; color: var(--vbp-text-muted); padding: 20px;">No se encontraron elementos</p>';
                return;
            }

            var html = '';
            encontrados.forEach(function(el) {
                html += '<div onclick="Alpine.store(\'vbp\').setSelection([\'' + el.id + '\']); document.getElementById(\'vbp-find-modal\').remove();" style="padding: 12px; background: var(--vbp-surface); border: 1px solid var(--vbp-border); border-radius: 6px; margin-bottom: 8px; cursor: pointer; transition: border-color 0.2s;">';
                html += '<div style="font-weight: 500;">' + (el.name || el.type) + '</div>';
                html += '<div style="font-size: 12px; color: var(--vbp-text-muted);">Tipo: ' + el.type + '</div>';
                html += '</div>';
            });

            results.innerHTML = html;
        });

        setTimeout(function() { input.focus(); }, 100);
    },

    /**
     * Toggle smart guides
     * Sincroniza con window.vbpCanvasUtils.smartGuides
     */
    toggleSmartGuides: function() {
        // Usar el sistema real de smartGuides de vbpCanvasUtils
        if (window.vbpCanvasUtils && window.vbpCanvasUtils.smartGuides) {
            this.smartGuidesEnabled = window.vbpCanvasUtils.smartGuides.toggle();
        } else {
            // Fallback si vbpCanvasUtils no esta disponible
            this.smartGuidesEnabled = !this.smartGuidesEnabled;
        }

        localStorage.setItem('vbp_smart_guides', this.smartGuidesEnabled ? 'true' : 'false');

        // Notificar con icono que indica el estado
        var iconoEstado = this.smartGuidesEnabled ? '🟢' : '🔴';
        window.vbpKeyboard.showNotification(iconoEstado + ' Smart guides ' + (this.smartGuidesEnabled ? 'activadas' : 'desactivadas'));
    },

    /**
     * Toggle measure tool
     */
    toggleMeasureTool: function() {
        this.measureToolEnabled = !this.measureToolEnabled;

        if (this.measureToolEnabled) {
            this.initMeasureTool();
        } else {
            this.removeMeasureTool();
        }

        window.vbpKeyboard.showNotification(this.measureToolEnabled ? '📏 Herramienta de medición activada' : '📏 Herramienta de medición desactivada');
    },

    /**
     * Inicializar herramienta de medición
     */
    initMeasureTool: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (!canvas) return;

        canvas.classList.add('vbp-measure-mode');
        canvas.style.cursor = 'crosshair';
    },

    /**
     * Remover herramienta de medición
     */
    removeMeasureTool: function() {
        var canvas = document.querySelector('.vbp-canvas');
        if (canvas) {
            canvas.classList.remove('vbp-measure-mode');
            canvas.style.cursor = '';
        }

        var measurements = document.querySelectorAll('.vbp-measurement');
        measurements.forEach(function(m) { m.remove(); });
    },

    /**
     * Toggle auto-layout
     */
    toggleAutoLayout: function() {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds) {
            window.vbpKeyboard.showNotification('Selecciona un contenedor', 'warning');
            return;
        }

        if (store.selection.elementIds.length === 0) {
            window.vbpKeyboard.showNotification('Selecciona un contenedor', 'warning');
            return;
        }

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element) {
                var currentLayout = element.styles && element.styles.layout;
                var isAutoLayout = currentLayout && currentLayout.display === 'flex';

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        layout: isAutoLayout
                            ? { display: 'block' }
                            : { display: 'flex', flexDirection: 'column', gap: '16px', alignItems: 'stretch' }
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('📐 Auto-layout toggled');
    },

    /**
     * Ajustar gap de auto-layout
     */
    adjustAutoLayoutGap: function(delta) {
        var store = Alpine.store('vbp');
        if (!store || !store.selection || !store.selection.elementIds || store.selection.elementIds.length === 0) return;

        store.saveToHistory();

        store.selection.elementIds.forEach(function(id) {
            var element = store.getElement(id);
            if (element && element.styles && element.styles.layout && element.styles.layout.gap) {
                var currentGap = parseInt(element.styles.layout.gap) || 16;
                var newGap = Math.max(0, currentGap + delta);

                store.updateElement(id, {
                    styles: Object.assign({}, element.styles, {
                        layout: Object.assign({}, element.styles.layout, {
                            gap: newGap + 'px'
                        })
                    })
                });
            }
        });

        store.isDirty = true;
        window.vbpKeyboard.showNotification('📐 Gap ajustado');
    },

    /**
     * Set breakpoint
     */
    setBreakpoint: function(breakpoint) {
        var store = Alpine.store('vbp');
        store.currentBreakpoint = breakpoint;

        var canvas = document.querySelector('.vbp-canvas');
        if (canvas) {
            canvas.classList.remove('vbp-breakpoint-desktop', 'vbp-breakpoint-tablet', 'vbp-breakpoint-mobile');
            canvas.classList.add('vbp-breakpoint-' + breakpoint);

            var widths = { desktop: '100%', tablet: '768px', mobile: '375px' };
            var canvasWrapper = canvas.parentElement;
            if (canvasWrapper) {
                canvasWrapper.style.maxWidth = widths[breakpoint];
            }
        }

        window.vbpKeyboard.showNotification('📱 ' + breakpoint.charAt(0).toUpperCase() + breakpoint.slice(1));
    }
};

// Cargar preferencias al inicializar
(function() {
    var storedFavorites = localStorage.getItem('vbp_favorites');
    if (storedFavorites) {
        window.VBPKeyboardTools.favorites = JSON.parse(storedFavorites);
    }

    var storedBookmarks = localStorage.getItem('vbp_bookmarks');
    if (storedBookmarks) {
        window.VBPKeyboardTools.bookmarks = JSON.parse(storedBookmarks);
    }

    var gridSize = localStorage.getItem('vbp_grid_size');
    if (gridSize) {
        window.VBPKeyboardTools.gridSize = parseInt(gridSize, 10);
    }

    var snapToGrid = localStorage.getItem('vbp_snap_to_grid');
    if (snapToGrid === 'true') {
        window.VBPKeyboardTools.snapToGridEnabled = true;
    }

    var smartGuides = localStorage.getItem('vbp_smart_guides');
    if (smartGuides !== null) {
        var smartGuidesEnabled = smartGuides === 'true';
        window.VBPKeyboardTools.smartGuidesEnabled = smartGuidesEnabled;

        // Sincronizar con el sistema real de smartGuides cuando este disponible
        var syncSmartGuides = function() {
            if (window.vbpCanvasUtils && window.vbpCanvasUtils.smartGuides) {
                window.vbpCanvasUtils.smartGuides.enabled = smartGuidesEnabled;
            }
        };

        // Intentar sincronizar inmediatamente o esperar a que cargue
        if (window.vbpCanvasUtils) {
            syncSmartGuides();
        } else {
            document.addEventListener('DOMContentLoaded', syncSmartGuides);
        }
    }
})();
