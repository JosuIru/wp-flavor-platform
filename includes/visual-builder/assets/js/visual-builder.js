/**
 * Flavor Visual Builder - JavaScript
 *
 * @package FlavorChatIA
 * @version 1.0.0
 */

(function($) {
    'use strict';

    const FlavorVB = {
        data: {},
        history: [],
        historyIndex: -1,
        selectedElement: null,
        mode: 'sections',
        postId: 0,
        isDirty: false,

        /**
         * Inicializar
         */
        init() {
            this.postId = $('#flavor-visual-builder').data('post-id');
            this.mode = $('#flavor_vb_mode').val();
            this.loadData();
            this.bindEvents();
            this.initSortable();
            this.setupAutosave();

            console.log('Flavor Visual Builder inicializado');
        },

        /**
         * Cargar datos
         */
        loadData() {
            const dataJson = $('#flavor_vb_data').val();
            try {
                this.data = JSON.parse(dataJson);
                if (!this.data.content) {
                    this.data.content = [];
                }
                this.renderCanvas();
            } catch (e) {
                console.error('Error al parsear datos:', e);
                this.data = {
                    mode: this.mode,
                    version: '1.0.0',
                    content: []
                };
            }
        },

        /**
         * Vincular eventos
         */
        bindEvents() {
            // Cambio de modo
            $('.flavor-vb-mode-btn').on('click', (e) => {
                const mode = $(e.currentTarget).data('mode');
                this.switchMode(mode);
            });

            // Botones de acción
            $('.flavor-vb-btn-save').on('click', () => this.save());
            $('.flavor-vb-btn-preview').on('click', () => this.preview());
            $('.flavor-vb-btn-undo').on('click', () => this.undo());
            $('.flavor-vb-btn-redo').on('click', () => this.redo());

            // Drag and drop de secciones
            $('.flavor-vb-section-item').on('click', (e) => {
                const section = $(e.currentTarget).data('section');
                this.addSection(section);
            });

            // Drag and drop de componentes
            $('.flavor-vb-component-item').on('click', (e) => {
                const component = $(e.currentTarget).data('component');
                this.addComponent(component);
            });

            // Prevenir salir sin guardar
            $(window).on('beforeunload', (e) => {
                if (this.isDirty) {
                    return '¿Seguro que quieres salir sin guardar?';
                }
            });
        },

        /**
         * Inicializar sortable
         */
        initSortable() {
            $('#flavor-vb-canvas-content').sortable({
                handle: '.flavor-vb-item-handle',
                placeholder: 'flavor-vb-placeholder',
                tolerance: 'pointer',
                update: () => {
                    this.updateFromCanvas();
                    this.markDirty();
                }
            });
        },

        /**
         * Setup autosave
         */
        setupAutosave() {
            setInterval(() => {
                if (this.isDirty) {
                    this.autosave();
                }
            }, 30000); // Cada 30 segundos
        },

        /**
         * Cambiar modo
         */
        switchMode(mode) {
            if (this.isDirty && !confirm('¿Cambiar de modo? Los cambios no guardados se perderán.')) {
                return;
            }

            this.mode = mode;
            $('#flavor_vb_mode').val(mode);

            // Actualizar UI
            $('.flavor-vb-mode-btn').removeClass('active');
            $(`.flavor-vb-mode-btn[data-mode="${mode}"]`).addClass('active');

            // Mostrar/ocultar paneles
            if (mode === 'sections') {
                $('.flavor-vb-panel-sections').show();
                $('.flavor-vb-panel-components').hide();
            } else {
                $('.flavor-vb-panel-sections').hide();
                $('.flavor-vb-panel-components').show();
            }

            // AJAX para cambiar modo en servidor
            $.ajax({
                url: flavorVB.ajax_url,
                type: 'POST',
                data: {
                    action: 'fvb_switch_mode',
                    nonce: flavorVB.nonce,
                    post_id: this.postId,
                    mode: mode
                },
                success: (response) => {
                    this.updateStatus('Modo cambiado a ' + (mode === 'sections' ? 'Secciones' : 'Componentes'));
                }
            });
        },

        /**
         * Añadir sección
         */
        addSection(sectionType) {
            const section = {
                id: 'section_' + Date.now(),
                type: 'section',
                component: sectionType,
                variant: 'default',
                data: this.getDefaultSectionData(sectionType)
            };

            this.data.content.push(section);
            this.renderCanvas();
            this.markDirty();
            this.pushHistory();
            this.updateStatus('Sección añadida: ' + sectionType);
        },

        /**
         * Añadir componente
         */
        addComponent(componentType) {
            const component = {
                id: 'component_' + Date.now(),
                type: 'component',
                component: componentType,
                data: this.getDefaultComponentData(componentType)
            };

            this.data.content.push(component);
            this.renderCanvas();
            this.markDirty();
            this.pushHistory();
            this.updateStatus('Componente añadido: ' + componentType);
        },

        /**
         * Datos por defecto de sección
         */
        getDefaultSectionData(type) {
            const defaults = {
                hero: {
                    titulo: 'Tu Título Principal Aquí',
                    subtitulo: 'Describe brevemente tu propuesta de valor',
                    cta_texto: 'Comenzar',
                    cta_url: '#',
                    color_fondo: '#f8fafc',
                    color_texto: '#1e293b'
                },
                features: {
                    titulo: 'Nuestras Características',
                    subtitulo: 'Todo lo que necesitas en un solo lugar',
                    items: [
                        { titulo: 'Característica 1', descripcion: 'Descripción breve', icono: 'dashicons-yes' },
                        { titulo: 'Característica 2', descripcion: 'Descripción breve', icono: 'dashicons-star-filled' },
                        { titulo: 'Característica 3', descripcion: 'Descripción breve', icono: 'dashicons-heart' }
                    ]
                },
                cta: {
                    titulo: '¿Listo para comenzar?',
                    descripcion: 'Únete a miles de usuarios satisfechos',
                    boton_texto: 'Empezar ahora',
                    boton_url: '#',
                    color_fondo: '#2271b1',
                    color_texto: '#ffffff'
                }
            };

            return defaults[type] || {};
        },

        /**
         * Datos por defecto de componente
         */
        getDefaultComponentData(type) {
            const defaults = {
                heading: {
                    text: 'Título',
                    level: 'h2',
                    align: 'left',
                    color: '#000000'
                },
                text: {
                    content: 'Tu texto aquí...',
                    align: 'left',
                    size: 'medium'
                },
                button: {
                    text: 'Botón',
                    url: '#',
                    style: 'primary',
                    size: 'medium'
                },
                image: {
                    url: '',
                    alt: '',
                    align: 'center',
                    width: '100%'
                }
            };

            return defaults[type] || {};
        },

        /**
         * Renderizar canvas
         */
        renderCanvas() {
            const $canvas = $('#flavor-vb-canvas-content');
            const $emptyState = $('#flavor-vb-empty-state');

            if (this.data.content.length === 0) {
                $emptyState.show();
                return;
            }

            $emptyState.hide();
            $canvas.empty();

            this.data.content.forEach((item, index) => {
                const $item = this.renderItem(item, index);
                $canvas.append($item);
            });

            // Bind eventos de items
            this.bindItemEvents();
        },

        /**
         * Renderizar item
         */
        renderItem(item, index) {
            const label = item.type === 'section'
                ? `Sección: ${item.component}`
                : `Componente: ${item.component}`;

            return $(`
                <div class="flavor-vb-canvas-item" data-index="${index}" data-id="${item.id}">
                    <div class="flavor-vb-item-header">
                        <span class="flavor-vb-item-handle dashicons dashicons-menu"></span>
                        <span class="flavor-vb-item-label">${label}</span>
                        <div class="flavor-vb-item-actions">
                            <button class="flavor-vb-item-edit" title="Editar">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button class="flavor-vb-item-duplicate" title="Duplicar">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                            <button class="flavor-vb-item-delete" title="Eliminar">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="flavor-vb-item-preview">
                        ${this.renderItemPreview(item)}
                    </div>
                </div>
            `);
        },

        /**
         * Renderizar preview de item
         */
        renderItemPreview(item) {
            if (item.type === 'section') {
                return `<div class="flavor-vb-section-preview">${this.renderSectionPreview(item)}</div>`;
            } else {
                return `<div class="flavor-vb-component-preview">${this.renderComponentPreview(item)}</div>`;
            }
        },

        /**
         * Preview de sección
         */
        renderSectionPreview(item) {
            const data = item.data || {};

            switch (item.component) {
                case 'hero':
                    return `
                        <div class="preview-hero">
                            <h1>${data.titulo || 'Título'}</h1>
                            <p>${data.subtitulo || 'Subtítulo'}</p>
                            <button>${data.cta_texto || 'CTA'}</button>
                        </div>
                    `;
                case 'features':
                    return `
                        <div class="preview-features">
                            <h2>${data.titulo || 'Features'}</h2>
                            <div class="features-grid">
                                ${(data.items || []).map(f => `<div class="feature-item">${f.titulo}</div>`).join('')}
                            </div>
                        </div>
                    `;
                default:
                    return `<div class="preview-generic">${item.component}</div>`;
            }
        },

        /**
         * Preview de componente
         */
        renderComponentPreview(item) {
            const data = item.data || {};

            switch (item.component) {
                case 'heading':
                    return `<${data.level || 'h2'}>${data.text || 'Título'}</${data.level || 'h2'}>`;
                case 'text':
                    return `<p>${data.content || 'Texto...'}</p>`;
                case 'button':
                    return `<button class="preview-button ${data.style}">${data.text || 'Botón'}</button>`;
                case 'image':
                    return data.url ? `<img src="${data.url}" alt="${data.alt}">` : '<div class="placeholder-image">📷 Imagen</div>';
                default:
                    return `<div class="preview-generic">${item.component}</div>`;
            }
        },

        /**
         * Bind eventos de items
         */
        bindItemEvents() {
            // Editar
            $('.flavor-vb-item-edit').on('click', (e) => {
                const index = $(e.currentTarget).closest('.flavor-vb-canvas-item').data('index');
                this.editItem(index);
            });

            // Duplicar
            $('.flavor-vb-item-duplicate').on('click', (e) => {
                const index = $(e.currentTarget).closest('.flavor-vb-canvas-item').data('index');
                this.duplicateItem(index);
            });

            // Eliminar
            $('.flavor-vb-item-delete').on('click', (e) => {
                const index = $(e.currentTarget).closest('.flavor-vb-canvas-item').data('index');
                this.deleteItem(index);
            });

            // Seleccionar
            $('.flavor-vb-canvas-item').on('click', (e) => {
                if (!$(e.target).closest('.flavor-vb-item-actions').length) {
                    const index = $(e.currentTarget).data('index');
                    this.selectItem(index);
                }
            });
        },

        /**
         * Editar item
         */
        editItem(index) {
            const item = this.data.content[index];
            this.selectedElement = item;
            this.showProperties(item);
        },

        /**
         * Duplicar item
         */
        duplicateItem(index) {
            const original = this.data.content[index];
            const duplicate = JSON.parse(JSON.stringify(original));
            duplicate.id = (original.type === 'section' ? 'section_' : 'component_') + Date.now();

            this.data.content.splice(index + 1, 0, duplicate);
            this.renderCanvas();
            this.markDirty();
            this.pushHistory();
        },

        /**
         * Eliminar item
         */
        deleteItem(index) {
            if (!confirm('¿Eliminar este elemento?')) {
                return;
            }

            this.data.content.splice(index, 1);
            this.renderCanvas();
            this.markDirty();
            this.pushHistory();
        },

        /**
         * Seleccionar item
         */
        selectItem(index) {
            $('.flavor-vb-canvas-item').removeClass('selected');
            $(`.flavor-vb-canvas-item[data-index="${index}"]`).addClass('selected');

            const item = this.data.content[index];
            this.selectedElement = item;
            this.showProperties(item);
        },

        /**
         * Mostrar propiedades
         */
        showProperties(item) {
            const $panel = $('#flavor-vb-properties-content');
            $panel.html(this.renderPropertiesForm(item));

            // Bind eventos de formulario
            $panel.find('input, textarea, select').on('change', () => {
                this.updateItemFromProperties();
            });
        },

        /**
         * Renderizar formulario de propiedades
         */
        renderPropertiesForm(item) {
            let html = `<div class="flavor-vb-properties-form">`;
            html += `<h4>${item.type === 'section' ? 'Sección' : 'Componente'}: ${item.component}</h4>`;

            // Campos según el tipo
            const data = item.data || {};

            if (item.component === 'hero') {
                html += this.renderField('text', 'titulo', 'Título', data.titulo);
                html += this.renderField('textarea', 'subtitulo', 'Subtítulo', data.subtitulo);
                html += this.renderField('text', 'cta_texto', 'Texto del botón', data.cta_texto);
                html += this.renderField('url', 'cta_url', 'URL del botón', data.cta_url);
                html += this.renderField('color', 'color_fondo', 'Color de fondo', data.color_fondo);
            } else if (item.component === 'heading') {
                html += this.renderField('text', 'text', 'Texto', data.text);
                html += this.renderField('select', 'level', 'Nivel', data.level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
                html += this.renderField('color', 'color', 'Color', data.color);
            } else if (item.component === 'button') {
                html += this.renderField('text', 'text', 'Texto', data.text);
                html += this.renderField('url', 'url', 'URL', data.url);
                html += this.renderField('select', 'style', 'Estilo', data.style, ['primary', 'secondary', 'outline']);
            }

            html += `</div>`;
            return html;
        },

        /**
         * Renderizar campo
         */
        renderField(type, name, label, value, options) {
            value = value || '';
            let html = `<div class="flavor-vb-field">`;
            html += `<label>${label}</label>`;

            if (type === 'textarea') {
                html += `<textarea name="${name}" class="flavor-vb-input">${value}</textarea>`;
            } else if (type === 'select') {
                html += `<select name="${name}" class="flavor-vb-input">`;
                options.forEach(opt => {
                    html += `<option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>`;
                });
                html += `</select>`;
            } else {
                html += `<input type="${type}" name="${name}" value="${value}" class="flavor-vb-input">`;
            }

            html += `</div>`;
            return html;
        },

        /**
         * Actualizar item desde propiedades
         */
        updateItemFromProperties() {
            if (!this.selectedElement) return;

            const $form = $('.flavor-vb-properties-form');
            $form.find('input, textarea, select').each((i, field) => {
                const $field = $(field);
                const name = $field.attr('name');
                const value = $field.val();

                if (name && this.selectedElement.data) {
                    this.selectedElement.data[name] = value;
                }
            });

            this.renderCanvas();
            this.markDirty();
        },

        /**
         * Actualizar desde canvas
         */
        updateFromCanvas() {
            const newContent = [];

            $('#flavor-vb-canvas-content .flavor-vb-canvas-item').each((i, el) => {
                const index = $(el).data('index');
                if (this.data.content[index]) {
                    newContent.push(this.data.content[index]);
                }
            });

            this.data.content = newContent;
        },

        /**
         * Guardar
         */
        save() {
            this.updateStatus('Guardando...');

            const dataJson = JSON.stringify(this.data);
            $('#flavor_vb_data').val(dataJson);

            $.ajax({
                url: flavorVB.ajax_url,
                type: 'POST',
                data: {
                    action: 'fvb_save_data',
                    nonce: flavorVB.nonce,
                    post_id: this.postId,
                    data: dataJson
                },
                success: (response) => {
                    if (response.success) {
                        this.isDirty = false;
                        this.updateStatus('✓ Guardado');
                        $('#flavor-vb-last-saved').text(new Date().toLocaleString('es-ES'));
                    } else {
                        this.updateStatus('✗ Error al guardar');
                    }
                },
                error: () => {
                    this.updateStatus('✗ Error de red');
                }
            });
        },

        /**
         * Autosave
         */
        autosave() {
            $.ajax({
                url: flavorVB.ajax_url,
                type: 'POST',
                data: {
                    action: 'fvb_autosave',
                    nonce: flavorVB.nonce,
                    post_id: this.postId,
                    data: JSON.stringify(this.data)
                },
                success: () => {
                    this.isDirty = false;
                    this.updateStatus('Autoguardado');
                }
            });
        },

        /**
         * Preview
         */
        preview() {
            this.updateStatus('Generando preview...');

            $.ajax({
                url: flavorVB.ajax_url,
                type: 'POST',
                data: {
                    action: 'fvb_preview',
                    nonce: flavorVB.nonce,
                    data: JSON.stringify(this.data)
                },
                success: (response) => {
                    if (response.success) {
                        this.showPreviewModal(response.data.html);
                    }
                }
            });
        },

        /**
         * Mostrar modal de preview
         */
        showPreviewModal(html) {
            const $modal = $('#flavor-vb-preview-modal');
            const $iframe = $('#flavor-vb-preview-frame');

            $iframe.contents().find('body').html(html);
            $modal.fadeIn();

            $('.flavor-vb-modal-close, .flavor-vb-modal-overlay').on('click', () => {
                $modal.fadeOut();
            });
        },

        /**
         * Undo
         */
        undo() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.data = JSON.parse(this.history[this.historyIndex]);
                this.renderCanvas();
                this.updateStatus('Deshecho');
            }
        },

        /**
         * Redo
         */
        redo() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.data = JSON.parse(this.history[this.historyIndex]);
                this.renderCanvas();
                this.updateStatus('Rehecho');
            }
        },

        /**
         * Push history
         */
        pushHistory() {
            // Remover futuro si estamos en el pasado
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            this.history.push(JSON.stringify(this.data));
            this.historyIndex++;

            // Limitar historial a 50 estados
            if (this.history.length > 50) {
                this.history.shift();
                this.historyIndex--;
            }
        },

        /**
         * Marcar como modificado
         */
        markDirty() {
            this.isDirty = true;
            this.updateStatus('* No guardado');
        },

        /**
         * Actualizar status
         */
        updateStatus(message) {
            $('#flavor-vb-status').text(message);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        if ($('#flavor-visual-builder').length) {
            FlavorVB.init();
        }
    });

    // Exponer globalmente
    window.FlavorVB = FlavorVB;

})(jQuery);
