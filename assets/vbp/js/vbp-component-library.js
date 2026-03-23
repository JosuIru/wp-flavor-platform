/**
 * Visual Builder Pro - Biblioteca de Componentes
 *
 * Sistema para guardar, gestionar y reutilizar componentes/bloques
 * en diferentes páginas del editor.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.21
 */

(function() {
    'use strict';

    /**
     * Biblioteca de Componentes VBP
     */
    window.VBPComponentLibrary = {
        componentes: [],
        categorias: [],
        componenteActual: null,
        cargando: false,
        filtroCategoria: '',
        filtroBusqueda: '',

        /**
         * Iconos SVG para las categorías
         */
        iconos: {
            layout: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
            star: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
            layers: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 2,7 12,12 22,7"/><polyline points="2,17 12,22 22,17"/><polyline points="2,12 12,17 22,12"/></svg>',
            menu: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>',
            grid: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'edit-3': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
            compass: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88"/></svg>',
            folder: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>',
            plus: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
            trash: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>',
            download: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7,10 12,15 17,10"/><path d="M12 15V3"/></svg>',
            upload: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17,8 12,3 7,8"/><path d="M12 3v12"/></svg>'
        },

        /**
         * Inicializar la biblioteca
         */
        init: function() {
            var self = this;
            this.cargarCategorias();
            this.cargarComponentes();
            this.configurarEventos();
        },

        /**
         * Cargar categorías desde la API
         */
        cargarCategorias: function() {
            var self = this;

            fetch(VBP_Config.restUrl + 'components/categories', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.categories) {
                    self.categorias = data.categories;
                    self.renderizarCategorias();
                }
            })
            .catch(function(error) {
                console.warn('[VBP] Error cargando categorías:', error);
            });
        },

        /**
         * Cargar componentes desde la API
         */
        cargarComponentes: function(filtros) {
            var self = this;
            self.cargando = true;
            self.actualizarEstadoCarga();

            var queryParams = new URLSearchParams();
            if (filtros && filtros.category) {
                queryParams.append('category', filtros.category);
            }
            if (filtros && filtros.search) {
                queryParams.append('search', filtros.search);
            }

            var url = VBP_Config.restUrl + 'components';
            if (queryParams.toString()) {
                url += '?' + queryParams.toString();
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.cargando = false;
                if (data.success && data.components) {
                    self.componentes = data.components;
                    self.renderizarComponentes();
                }
            })
            .catch(function(error) {
                self.cargando = false;
                console.warn('[VBP] Error cargando componentes:', error);
                self.mostrarError('Error al cargar componentes');
            });
        },

        /**
         * Guardar componente
         */
        guardarComponente: function(nombre, bloques, categoria, opciones) {
            var self = this;
            categoria = categoria || 'custom';
            opciones = opciones || {};

            return fetch(VBP_Config.restUrl + 'components', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify({
                    name: nombre,
                    blocks: bloques,
                    category: categoria,
                    description: opciones.description || '',
                    tags: opciones.tags || '',
                    is_global: opciones.is_global || false
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.cargarComponentes();
                    self.cargarCategorias();
                    self.mostrarExito('Componente guardado correctamente');
                    return data;
                } else {
                    throw new Error(data.message || 'Error al guardar');
                }
            })
            .catch(function(error) {
                self.mostrarError('Error: ' + error.message);
                throw error;
            });
        },

        /**
         * Eliminar componente
         */
        eliminarComponente: function(componenteId) {
            var self = this;

            if (!confirm('¿Eliminar este componente? Esta acción no se puede deshacer.')) {
                return Promise.resolve(false);
            }

            return fetch(VBP_Config.restUrl + 'components/' + componenteId, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.cargarComponentes();
                    self.cargarCategorias();
                    self.mostrarExito('Componente eliminado');
                    return true;
                } else {
                    throw new Error(data.message || 'Error al eliminar');
                }
            })
            .catch(function(error) {
                self.mostrarError('Error: ' + error.message);
                return false;
            });
        },

        /**
         * Insertar componente en el canvas
         */
        insertarComponente: function(componenteId) {
            var self = this;

            fetch(VBP_Config.restUrl + 'components/' + componenteId, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.component && data.component.blocks) {
                    var store = Alpine.store('vbp');
                    var bloques = data.component.blocks;

                    // Si es un array de bloques
                    if (Array.isArray(bloques)) {
                        bloques.forEach(function(bloque) {
                            self.insertarBloque(bloque, store);
                        });
                    } else {
                        // Si es un solo bloque
                        self.insertarBloque(bloques, store);
                    }

                    self.mostrarExito('Componente insertado');

                    // Cerrar el panel de componentes si está en modal
                    var modal = document.querySelector('.vbp-components-modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                }
            })
            .catch(function(error) {
                self.mostrarError('Error al insertar componente');
            });
        },

        /**
         * Insertar un bloque en el store
         */
        insertarBloque: function(bloque, store) {
            // Generar nuevo ID para evitar duplicados
            var nuevoBloque = JSON.parse(JSON.stringify(bloque));
            nuevoBloque.id = 'el_' + Math.random().toString(36).substr(2, 9);

            // Regenerar IDs de hijos si existen
            if (nuevoBloque.children && nuevoBloque.children.length > 0) {
                this.regenerarIdsHijos(nuevoBloque.children);
            }

            store.elements.push(nuevoBloque);
            store.markAsDirty();
            store.setSelection([nuevoBloque.id]);
        },

        /**
         * Regenerar IDs de hijos recursivamente
         */
        regenerarIdsHijos: function(children) {
            var self = this;
            children.forEach(function(child) {
                child.id = 'el_' + Math.random().toString(36).substr(2, 9);
                if (child.children && child.children.length > 0) {
                    self.regenerarIdsHijos(child.children);
                }
            });
        },

        /**
         * Exportar componente
         */
        exportarComponente: function(componenteId) {
            var self = this;

            fetch(VBP_Config.restUrl + 'components/' + componenteId + '/export', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var json = JSON.stringify(data.data, null, 2);
                    var blob = new Blob([json], { type: 'application/json' });
                    var url = URL.createObjectURL(blob);
                    var nombreArchivo = (data.data.name || 'componente').toLowerCase().replace(/\s+/g, '-') + '.json';

                    var enlace = document.createElement('a');
                    enlace.href = url;
                    enlace.download = nombreArchivo;
                    document.body.appendChild(enlace);
                    enlace.click();
                    document.body.removeChild(enlace);
                    URL.revokeObjectURL(url);

                    self.mostrarExito('Componente exportado');
                }
            })
            .catch(function(error) {
                self.mostrarError('Error al exportar componente');
            });
        },

        /**
         * Importar componente desde archivo
         */
        importarComponente: function(archivo) {
            var self = this;
            var reader = new FileReader();

            reader.onload = function(e) {
                try {
                    var datos = JSON.parse(e.target.result);

                    fetch(VBP_Config.restUrl + 'components/import', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': VBP_Config.restNonce
                        },
                        body: JSON.stringify(datos)
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            self.cargarComponentes();
                            self.cargarCategorias();
                            self.mostrarExito('Componente importado correctamente');
                        } else {
                            throw new Error(data.message || 'Error al importar');
                        }
                    })
                    .catch(function(error) {
                        self.mostrarError('Error: ' + error.message);
                    });
                } catch (error) {
                    self.mostrarError('Archivo JSON inválido');
                }
            };

            reader.readAsText(archivo);
        },

        /**
         * Guardar elemento seleccionado como componente
         */
        guardarSeleccionComoComponente: function() {
            var store = Alpine.store('vbp');
            if (!store || store.selection.elementIds.length === 0) {
                this.mostrarError('Selecciona un elemento primero');
                return;
            }

            var elementosSeleccionados = [];
            var self = this;

            store.selection.elementIds.forEach(function(id) {
                var elemento = store.getElementDeep(id);
                if (elemento) {
                    elementosSeleccionados.push(elemento);
                }
            });

            if (elementosSeleccionados.length === 0) {
                this.mostrarError('No se encontraron elementos válidos');
                return;
            }

            // Mostrar modal para nombrar el componente
            this.mostrarModalGuardar(elementosSeleccionados);
        },

        /**
         * Mostrar modal para guardar componente
         */
        mostrarModalGuardar: function(elementos) {
            var self = this;
            var nombrePorDefecto = elementos.length === 1 ? elementos[0].name : 'Mi Componente';

            // Crear modal
            var modalHtml = '<div class="vbp-save-component-modal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10001; display: flex; align-items: center; justify-content: center;">' +
                '<div class="vbp-modal-content" style="background: var(--vbp-bg-primary, #fff); border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">' +
                '<div class="vbp-modal-header" style="padding: 20px; border-bottom: 1px solid var(--vbp-border-color, #e5e7eb);">' +
                '<h3 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--vbp-text-primary, #111);">Guardar como Componente</h3>' +
                '</div>' +
                '<div class="vbp-modal-body" style="padding: 20px;">' +
                '<div style="margin-bottom: 16px;">' +
                '<label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: var(--vbp-text-primary, #111);">Nombre</label>' +
                '<input type="text" id="vbp-component-name" value="' + nombrePorDefecto + '" style="width: 100%; padding: 10px 12px; border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 8px; font-size: 14px; box-sizing: border-box;">' +
                '</div>' +
                '<div style="margin-bottom: 16px;">' +
                '<label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: var(--vbp-text-primary, #111);">Categoría</label>' +
                '<select id="vbp-component-category" style="width: 100%; padding: 10px 12px; border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 8px; font-size: 14px; box-sizing: border-box;">' +
                self.categorias.map(function(cat) {
                    return '<option value="' + cat.id + '">' + cat.name + '</option>';
                }).join('') +
                '</select>' +
                '</div>' +
                '<div style="margin-bottom: 16px;">' +
                '<label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: var(--vbp-text-primary, #111);">Descripción (opcional)</label>' +
                '<textarea id="vbp-component-description" rows="2" style="width: 100%; padding: 10px 12px; border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>' +
                '</div>' +
                '<div style="margin-bottom: 16px;">' +
                '<label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; color: var(--vbp-text-primary, #111);">Tags (separados por coma)</label>' +
                '<input type="text" id="vbp-component-tags" placeholder="hero, landing, destacado" style="width: 100%; padding: 10px 12px; border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 8px; font-size: 14px; box-sizing: border-box;">' +
                '</div>' +
                '</div>' +
                '<div class="vbp-modal-footer" style="padding: 16px 20px; border-top: 1px solid var(--vbp-border-color, #e5e7eb); display: flex; justify-content: flex-end; gap: 12px;">' +
                '<button type="button" class="vbp-btn-cancel" style="padding: 10px 20px; border: 1px solid var(--vbp-border-color, #e5e7eb); background: transparent; border-radius: 8px; cursor: pointer; font-size: 14px;">Cancelar</button>' +
                '<button type="button" class="vbp-btn-save" style="padding: 10px 20px; background: var(--vbp-accent-color, #6366f1); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">Guardar</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            var modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            var modal = modalContainer.firstChild;
            document.body.appendChild(modal);

            // Eventos
            modal.querySelector('.vbp-btn-cancel').addEventListener('click', function() {
                modal.remove();
            });

            modal.querySelector('.vbp-btn-save').addEventListener('click', function() {
                var nombre = document.getElementById('vbp-component-name').value.trim();
                var categoria = document.getElementById('vbp-component-category').value;
                var descripcion = document.getElementById('vbp-component-description').value.trim();
                var tags = document.getElementById('vbp-component-tags').value.trim();

                if (!nombre) {
                    self.mostrarError('El nombre es requerido');
                    return;
                }

                self.guardarComponente(nombre, elementos, categoria, {
                    description: descripcion,
                    tags: tags
                }).then(function() {
                    modal.remove();
                }).catch(function() {
                    // Error ya manejado en guardarComponente
                });
            });

            // Cerrar con clic fuera
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });

            // Enfocar input
            setTimeout(function() {
                var input = document.getElementById('vbp-component-name');
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 100);
        },

        /**
         * Renderizar categorías en el sidebar
         */
        renderizarCategorias: function() {
            var contenedor = document.getElementById('vbp-component-categories');
            if (!contenedor) return;

            var self = this;
            var html = '<button class="vbp-category-btn' + (!self.filtroCategoria ? ' active' : '') + '" data-category="" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: none; background: ' + (!self.filtroCategoria ? 'var(--vbp-accent-color, #6366f1)' : 'transparent') + '; color: ' + (!self.filtroCategoria ? 'white' : 'var(--vbp-text-primary, #111)') + '; border-radius: 6px; cursor: pointer; width: 100%; text-align: left; font-size: 13px;">' +
                '<span>Todos</span>' +
                '<span style="margin-left: auto; opacity: 0.7;">' + self.componentes.length + '</span>' +
                '</button>';

            self.categorias.forEach(function(cat) {
                var isActive = self.filtroCategoria === cat.id;
                html += '<button class="vbp-category-btn' + (isActive ? ' active' : '') + '" data-category="' + cat.id + '" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: none; background: ' + (isActive ? 'var(--vbp-accent-color, #6366f1)' : 'transparent') + '; color: ' + (isActive ? 'white' : 'var(--vbp-text-primary, #111)') + '; border-radius: 6px; cursor: pointer; width: 100%; text-align: left; font-size: 13px;">' +
                    '<span style="display: flex; align-items: center;">' + (self.iconos[cat.icon] || '') + '</span>' +
                    '<span>' + cat.name + '</span>' +
                    '<span style="margin-left: auto; opacity: 0.7;">' + cat.count + '</span>' +
                    '</button>';
            });

            contenedor.innerHTML = html;

            // Eventos de clic
            contenedor.querySelectorAll('.vbp-category-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.filtroCategoria = this.dataset.category;
                    self.cargarComponentes({ category: self.filtroCategoria, search: self.filtroBusqueda });
                    self.renderizarCategorias();
                });
            });
        },

        /**
         * Renderizar componentes
         */
        renderizarComponentes: function() {
            var contenedor = document.getElementById('vbp-components-list');
            if (!contenedor) return;

            var self = this;

            if (self.componentes.length === 0) {
                contenedor.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: var(--vbp-text-secondary, #6b7280);">' +
                    '<div style="font-size: 32px; margin-bottom: 12px;">📦</div>' +
                    '<p style="margin: 0 0 8px; font-weight: 500;">No hay componentes</p>' +
                    '<p style="margin: 0; font-size: 13px; opacity: 0.8;">Guarda elementos como componentes para reutilizarlos</p>' +
                    '</div>';
                return;
            }

            var html = '<div class="vbp-components-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; padding: 4px;">';

            self.componentes.forEach(function(componente) {
                var thumbnail = componente.thumbnail || '';
                var placeholderBg = 'linear-gradient(135deg, var(--vbp-accent-color, #6366f1), #8b5cf6)';

                html += '<div class="vbp-component-card" data-component-id="' + componente.id + '" style="background: var(--vbp-bg-secondary, #f9fafb); border: 1px solid var(--vbp-border-color, #e5e7eb); border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.2s;">' +
                    '<div class="vbp-component-preview" style="height: 80px; background: ' + (thumbnail ? 'url(' + thumbnail + ') center/cover' : placeholderBg) + '; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">' +
                    (thumbnail ? '' : '📦') +
                    '</div>' +
                    '<div class="vbp-component-info" style="padding: 10px;">' +
                    '<div style="font-weight: 500; font-size: 13px; color: var(--vbp-text-primary, #111); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + componente.name + '</div>' +
                    '<div style="font-size: 11px; color: var(--vbp-text-secondary, #6b7280); margin-top: 2px;">' + self.obtenerNombreCategoria(componente.category) + '</div>' +
                    '</div>' +
                    '<div class="vbp-component-actions" style="display: none; position: absolute; top: 4px; right: 4px; gap: 4px;">' +
                    '<button class="vbp-action-export" title="Exportar" style="padding: 4px; background: rgba(255,255,255,0.9); border: none; border-radius: 4px; cursor: pointer;">' + self.iconos.download + '</button>' +
                    '<button class="vbp-action-delete" title="Eliminar" style="padding: 4px; background: rgba(255,255,255,0.9); border: none; border-radius: 4px; cursor: pointer; color: #ef4444;">' + self.iconos.trash + '</button>' +
                    '</div>' +
                    '</div>';
            });

            html += '</div>';
            contenedor.innerHTML = html;

            // Eventos de clic
            contenedor.querySelectorAll('.vbp-component-card').forEach(function(card) {
                var componenteId = card.dataset.componentId;

                // Clic en la tarjeta = insertar
                card.addEventListener('click', function(e) {
                    if (e.target.closest('.vbp-action-export') || e.target.closest('.vbp-action-delete')) {
                        return;
                    }
                    self.insertarComponente(componenteId);
                });

                // Hover para mostrar acciones
                card.addEventListener('mouseenter', function() {
                    card.style.borderColor = 'var(--vbp-accent-color, #6366f1)';
                    card.style.transform = 'translateY(-2px)';
                    card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                    var actions = card.querySelector('.vbp-component-actions');
                    if (actions) actions.style.display = 'flex';
                });

                card.addEventListener('mouseleave', function() {
                    card.style.borderColor = '';
                    card.style.transform = '';
                    card.style.boxShadow = '';
                    var actions = card.querySelector('.vbp-component-actions');
                    if (actions) actions.style.display = 'none';
                });

                // Botón exportar
                var btnExportar = card.querySelector('.vbp-action-export');
                if (btnExportar) {
                    btnExportar.addEventListener('click', function(e) {
                        e.stopPropagation();
                        self.exportarComponente(componenteId);
                    });
                }

                // Botón eliminar
                var btnEliminar = card.querySelector('.vbp-action-delete');
                if (btnEliminar) {
                    btnEliminar.addEventListener('click', function(e) {
                        e.stopPropagation();
                        self.eliminarComponente(componenteId);
                    });
                }
            });
        },

        /**
         * Obtener nombre de categoría
         */
        obtenerNombreCategoria: function(categoryId) {
            var cat = this.categorias.find(function(c) { return c.id === categoryId; });
            return cat ? cat.name : 'Personalizado';
        },

        /**
         * Configurar eventos
         */
        configurarEventos: function() {
            var self = this;

            // Botón de búsqueda
            var inputBusqueda = document.getElementById('vbp-component-search');
            if (inputBusqueda) {
                var debounceTimer;
                inputBusqueda.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        self.filtroBusqueda = inputBusqueda.value.trim();
                        self.cargarComponentes({ category: self.filtroCategoria, search: self.filtroBusqueda });
                    }, 300);
                });
            }

            // Botón de importar
            var btnImportar = document.getElementById('vbp-import-component');
            if (btnImportar) {
                btnImportar.addEventListener('click', function() {
                    var input = document.createElement('input');
                    input.type = 'file';
                    input.accept = '.json';
                    input.onchange = function(e) {
                        if (e.target.files.length > 0) {
                            self.importarComponente(e.target.files[0]);
                        }
                    };
                    input.click();
                });
            }
        },

        /**
         * Actualizar estado de carga
         */
        actualizarEstadoCarga: function() {
            var contenedor = document.getElementById('vbp-components-list');
            if (!contenedor || !this.cargando) return;

            contenedor.innerHTML = '<div style="text-align: center; padding: 40px 20px;">' +
                '<div class="vbp-spinner" style="width: 32px; height: 32px; border: 3px solid var(--vbp-border-color, #e5e7eb); border-top-color: var(--vbp-accent-color, #6366f1); border-radius: 50%; margin: 0 auto 12px; animation: vbp-spin 0.8s linear infinite;"></div>' +
                '<p style="margin: 0; color: var(--vbp-text-secondary, #6b7280);">Cargando componentes...</p>' +
                '</div>';
        },

        /**
         * Mostrar notificación de éxito
         */
        mostrarExito: function(mensaje) {
            if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbp')) {
                // Usar el sistema de notificaciones de VBP si está disponible
                var app = document.querySelector('[x-data="vbpApp()"]');
                if (app && app.__x && app.__x.$data && app.__x.$data.showNotification) {
                    app.__x.$data.showNotification(mensaje, 'success');
                    return;
                }
            }
            console.log('[VBP] ' + mensaje);
        },

        /**
         * Mostrar notificación de error
         */
        mostrarError: function(mensaje) {
            if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbp')) {
                var app = document.querySelector('[x-data="vbpApp()"]');
                if (app && app.__x && app.__x.$data && app.__x.$data.showNotification) {
                    app.__x.$data.showNotification(mensaje, 'error');
                    return;
                }
            }
            console.error('[VBP] ' + mensaje);
        }
    };

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar a que VBP_Config esté disponible
        if (typeof VBP_Config !== 'undefined') {
            window.VBPComponentLibrary.init();
        }
    });

    // También inicializar si Alpine ya está cargado
    document.addEventListener('alpine:init', function() {
        setTimeout(function() {
            if (typeof VBP_Config !== 'undefined') {
                window.VBPComponentLibrary.init();
            }
        }, 500);
    });

})();
