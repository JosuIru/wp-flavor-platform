/**
 * Flavor Landing Editor - Drag & Drop Visual Editor
 *
 * Editor visual para crear landing pages con drag & drop nativo HTML5
 */
(function ($) {
	'use strict';

	// Namespace
	window.FlavorLandingEditor = window.FlavorLandingEditor || {};

	/**
     * Editor principal
     */
	FlavorLandingEditor.Editor = {
		// Estado
		sections: [],
		selectedSection: null,
		draggedElement: null,
		isDirty: false,
		autosaveTimer: null,
		currentSectionCategory: 'all',
		currentSearchTerm: '',

		// Sistema de historial mejorado (Undo/Redo)
		historyStack: [],
		historyIndex: -1,
		maxHistory: 50,

		// Sistema de Zoom
		zoomLevel: 100,
		zoomLevels: [50, 75, 100, 125, 150],

		// Device Preview
		currentDevice: 'desktop',
		deviceWidths: {
			desktop: '100%',
			tablet: '768px',
			mobile: '375px'
		},

		// Plantillas
		templates: [],
		templatesLoaded: false,

		/**
         * Inicializa el editor
         */
		init: function () {
			this.bindEvents();
			this.loadStructure();
			this.initColorPickers();
			this.startAutosave();
			this.initKeyboardShortcuts();
			this.initZoom();
			this.initDevicePreview();
			this.loadTemplates();
			this.updateUndoRedoButtons();
		},

		/**
         * Bindea eventos
         */
		bindEvents: function () {
			var self = this;

			// Drag & Drop desde panel de secciones
			$(document).on('dragstart', '.flavor-editor-section-item', function (e) {
				self.handleDragStart(e, 'new');
			});

			// Drag & Drop de secciones existentes en canvas
			$(document).on('dragstart', '.flavor-canvas-section', function (e) {
				self.handleDragStart(e, 'reorder');
			});

			$(document).on('dragend', '.flavor-editor-section-item, .flavor-canvas-section', function (e) {
				self.handleDragEnd(e);
			});

			// Canvas drop zone
			$('#flavor-editor-canvas').on('dragover', function (e) {
				self.handleDragOver(e);
			});

			$('#flavor-editor-canvas').on('dragleave', function (e) {
				self.handleDragLeave(e);
			});

			$('#flavor-editor-canvas').on('drop', function (e) {
				self.handleDrop(e);
			});

			// Seleccionar sección
			$(document).on('click', '.flavor-canvas-section', function (e) {
				if (!$(e.target).closest('.flavor-section-actions').length) {
					self.selectSection($(this).data('section-id'));
				}
			});

			// Acciones de sección
			$(document).on('click', '.flavor-section-action-delete', function (e) {
				e.stopPropagation();
				var sectionId = $(this).closest('.flavor-canvas-section').data('section-id');
				self.deleteSection(sectionId);
			});

			$(document).on('click', '.flavor-section-action-duplicate', function (e) {
				e.stopPropagation();
				var sectionId = $(this).closest('.flavor-canvas-section').data('section-id');
				self.duplicateSection(sectionId);
			});

			$(document).on('click', '.flavor-section-action-up', function (e) {
				e.stopPropagation();
				var sectionId = $(this).closest('.flavor-canvas-section').data('section-id');
				self.moveSection(sectionId, -1);
			});

			$(document).on('click', '.flavor-section-action-down', function (e) {
				e.stopPropagation();
				var sectionId = $(this).closest('.flavor-canvas-section').data('section-id');
				self.moveSection(sectionId, 1);
			});

			// Cambios en opciones
			$(document).on('change input', '#flavor-editor-options input, #flavor-editor-options textarea, #flavor-editor-options select', function () {
				self.updateSectionData($(this));
			});

			// Cambio de variante
			$(document).on('change', '.flavor-variant-selector', function () {
				self.updateSectionVariant($(this).val());
			});

			// Guardar
			$('#flavor-editor-save').on('click', function () {
				self.save();
			});

			// Preview
			$('#flavor-editor-preview').on('click', function () {
				self.refreshPreview();
			});

			// Responsive buttons
			$('.flavor-preview-device').on('click', function () {
				var device = $(this).data('device');
				self.setPreviewDevice(device);
				$('.flavor-preview-device').removeClass('active');
				$(this).addClass('active');
			});

			// Undo
			$('#flavor-editor-undo').on('click', function () {
				self.undo();
			});

			// Redo
			$('#flavor-editor-redo').on('click', function () {
				self.redo();
			});

			// Zoom controls
			$('#flavor-zoom-in').on('click', function () {
				self.zoomIn();
			});

			$('#flavor-zoom-out').on('click', function () {
				self.zoomOut();
			});

			$('#flavor-zoom-reset').on('click', function () {
				self.setZoom(100);
			});

			$('#flavor-zoom-select').on('change', function () {
				self.setZoom(parseInt($(this).val()));
			});

			// Device preview toggle
			$('.flavor-device-btn').on('click', function () {
				var device = $(this).data('device');
				self.setDevice(device);
			});

			// Plantillas
			$('#flavor-templates-btn').on('click', function () {
				self.openTemplatesModal();
			});

			// Cerrar modal de plantillas
			$(document).on('click', '.flavor-templates-modal-close, .flavor-templates-modal-overlay', function () {
				self.closeTemplatesModal();
			});

			// Seleccionar plantilla
			$(document).on('click', '.flavor-template-card', function () {
				var templateId = $(this).data('template-id');
				self.loadTemplate(templateId);
			});

			// Filtrar plantillas por categoría
			$(document).on('click', '.flavor-template-category-btn', function () {
				var category = $(this).data('category');
				self.filterTemplates(category);
				$('.flavor-template-category-btn').removeClass('active');
				$(this).addClass('active');
			});

			// Toggle sidebar (secciones)
			$('#flavor-toggle-sidebar').on('click', function () {
				self.toggleSidebar();
			});

			// Toggle canvas (editor main)
			$('#flavor-toggle-canvas').on('click', function () {
				self.toggleCanvas();
			});

			// Cerrar panel de opciones
			$('#flavor-options-close').on('click', function () {
				self.deselectSection();
			});

			// Buscar secciones
			$('#flavor-section-search').on('input', function () {
				self.currentSearchTerm = $(this).val();
				self.filterSections();
			});

			// Filtros por categoria de secciones
			$(document).on('click', '.flavor-section-filter', function () {
				self.currentSectionCategory = $(this).data('category') || 'all';
				$('.flavor-section-filter').removeClass('active');
				$(this).addClass('active');
				self.filterSections();
			});

			// Repeater: añadir item
			$(document).on('click', '.flavor-repeater-add', function () {
				self.addRepeaterItem($(this).closest('.flavor-field-repeater'));
			});

			// Repeater: eliminar item
			$(document).on('click', '.flavor-repeater-remove', function () {
				self.removeRepeaterItem($(this).closest('.flavor-repeater-item'));
			});

			// Selector de imagen
			$(document).on('click', '.flavor-image-select', function () {
				self.openMediaLibrary($(this));
			});

			$(document).on('click', '.flavor-image-remove', function () {
				self.removeImage($(this));
			});

			// Galería
			$(document).on('click', '.flavor-gallery-select', function () {
				self.openGallerySelector($(this));
			});

			// Selector de icono
			$(document).on('click', '.flavor-icon-trigger', function () {
				self.openIconPicker($(this));
			});

			$(document).on('click', '.flavor-icon-option', function () {
				self.selectIcon($(this));
			});

			// Cerrar icon picker al hacer click fuera
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.flavor-icon-picker').length && !$(e.target).hasClass('flavor-icon-trigger')) {
					$('.flavor-icon-picker').removeClass('active');
				}
			});

			// Filtrar iconos
			$(document).on('input', '.flavor-icon-search', function () {
				var query = $(this).val().toLowerCase();
				$(this).closest('.flavor-icon-picker').find('.flavor-icon-option').each(function () {
					var iconName = $(this).data('icon');
					$(this).toggle(iconName.indexOf(query) !== -1);
				});
			});

			// Color primario global
			$('#flavor-global-color').on('change', function () {
				self.updateGlobalColor($(this).val());
			});

			// === EDICIÓN INLINE ===

			// Prevenir drag cuando se edita inline
			$(document).on('focus', '.flavor-inline-editable', function () {
				$(this).closest('[draggable]').attr('draggable', 'false');
			});

			$(document).on('blur', '.flavor-inline-editable', function () {
				$(this).closest('.flavor-canvas-section').attr('draggable', 'true');
			});

			// Guardar cambios inline al perder foco
			$(document).on('blur', '.flavor-inline-editable', function () {
				var $el = $(this);
				var field = $el.data('field');
				var sectionId = $el.closest('.flavor-preview-content').data('section-id');
				var newValue = $el.text().trim();

				if (sectionId && field) {
					self.updateInlineField(sectionId, field, newValue);
				}
			});

			// Enter para confirmar, Escape para cancelar
			$(document).on('keydown', '.flavor-inline-editable', function (e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					$(this).blur();
				}
				if (e.key === 'Escape') {
					e.preventDefault();
					// Restaurar valor original
					var field = $(this).data('field');
					var sectionId = $(this).closest('.flavor-preview-content').data('section-id');
					var section = self.getSection(sectionId);
					if (section && section.data[field]) {
						$(this).text(section.data[field]);
					}
					$(this).blur();
				}
			});

			// Seleccionar texto al hacer clic
			$(document).on('click', '.flavor-inline-editable', function (e) {
				e.stopPropagation();
				// Seleccionar todo el texto
				var range = document.createRange();
				range.selectNodeContents(this);
				var sel = window.getSelection();
				sel.removeAllRanges();
				sel.addRange(range);
			});

			// Confirmar antes de salir si hay cambios
			$(window).on('beforeunload', function () {
				if (self.isDirty) {
					return flavorLandingEditor.i18n.unsavedChanges;
				}
			});
		},

		/**
         * Inicia drag
         */
		handleDragStart: function (e, type) {
			var $target = $(e.target).closest('[draggable="true"]');
			this.draggedElement = {
				type: type,
				sectionType: $target.data('section-type'),
				sectionId: $target.data('section-id')
			};

			e.originalEvent.dataTransfer.effectAllowed = 'move';
			e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(this.draggedElement));

			$target.addClass('dragging');
			$('#flavor-editor-canvas').addClass('drag-active');
		},

		/**
         * Finaliza drag
         */
		handleDragEnd: function (e) {
			$('.dragging').removeClass('dragging');
			$('#flavor-editor-canvas').removeClass('drag-active');
			$('.drop-indicator').remove();
			this.draggedElement = null;
		},

		/**
         * Durante drag over canvas
         */
		handleDragOver: function (e) {
			e.preventDefault();
			e.originalEvent.dataTransfer.dropEffect = 'move';

			var $canvas = $('#flavor-editor-canvas');
			var mouseY = e.originalEvent.clientY;

			// Eliminar indicadores anteriores
			$('.drop-indicator').remove();

			// Encontrar posición de inserción
			var $sections = $canvas.find('.flavor-canvas-section');
			var insertIndex = $sections.length;

			$sections.each(function (index) {
				var rect = this.getBoundingClientRect();
				var midpoint = rect.top + rect.height / 2;

				if (mouseY < midpoint) {
					insertIndex = index;
					return false;
				}
			});

			// Mostrar indicador
			var $indicator = $('<div class="drop-indicator"></div>');

			if (insertIndex === 0) {
				$canvas.prepend($indicator);
			} else if (insertIndex >= $sections.length) {
				$canvas.append($indicator);
			} else {
				$sections.eq(insertIndex).before($indicator);
			}
		},

		/**
         * Sale del canvas
         */
		handleDragLeave: function (e) {
			if (!$(e.relatedTarget).closest('#flavor-editor-canvas').length) {
				$('.drop-indicator').remove();
			}
		},

		/**
         * Drop en canvas
         */
		handleDrop: function (e) {
			e.preventDefault();

			var data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
			var $indicator = $('.drop-indicator');
			var insertIndex = $indicator.index();

			$('.drop-indicator').remove();

			if (data.type === 'new') {
				this.addSection(data.sectionType, insertIndex);
			} else if (data.type === 'reorder') {
				this.reorderSection(data.sectionId, insertIndex);
			}

			this.saveToHistory();
			this.isDirty = true;
		},

		/**
         * Añade nueva sección
         */
		addSection: function (type, index) {
			var sectionConfig = flavorLandingEditor.sections[type];
			if (!sectionConfig) {return;}

			var sectionId = 'section-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

			// Crear datos por defecto
			var sectionData = {
				id: sectionId,
				type: type,
				variant: Object.keys(sectionConfig.variants)[0] || 'default',
				data: this.getDefaultData(sectionConfig.fields)
			};

			// Insertar en posición
			if (index >= 0 && index < this.sections.length) {
				this.sections.splice(index, 0, sectionData);
			} else {
				this.sections.push(sectionData);
			}

			this.renderCanvas();
			this.selectSection(sectionId);
			this.refreshPreview();
		},

		/**
         * Obtiene datos por defecto de campos
         */
		getDefaultData: function (fields) {
			var data = {};

			for (var fieldName in fields) {
				var field = fields[fieldName];
				if (field.type === 'repeater' && field.default) {
					data[fieldName] = JSON.parse(JSON.stringify(field.default));
				} else {
					data[fieldName] = field.default !== undefined ? field.default : '';
				}
			}

			return data;
		},

		/**
         * Reordena sección
         */
		reorderSection: function (sectionId, newIndex) {
			var currentIndex = this.getSectionIndex(sectionId);
			if (currentIndex === -1) {return;}

			var section = this.sections.splice(currentIndex, 1)[0];

			// Ajustar índice si es necesario
			if (newIndex > currentIndex) {
				newIndex--;
			}

			if (newIndex >= 0 && newIndex <= this.sections.length) {
				this.sections.splice(newIndex, 0, section);
			} else {
				this.sections.push(section);
			}

			this.renderCanvas();
			this.refreshPreview();
		},

		/**
         * Elimina sección
         */
		deleteSection: function (sectionId) {
			if (!confirm(flavorLandingEditor.i18n.confirmDelete)) {return;}

			this.saveToHistory();

			var index = this.getSectionIndex(sectionId);
			if (index !== -1) {
				this.sections.splice(index, 1);
			}

			if (this.selectedSection === sectionId) {
				this.deselectSection();
			}

			this.renderCanvas();
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Duplica sección
         */
		duplicateSection: function (sectionId) {
			this.saveToHistory();

			var index = this.getSectionIndex(sectionId);
			if (index === -1) {return;}

			var original = this.sections[index];
			var duplicate = JSON.parse(JSON.stringify(original));
			duplicate.id = 'section-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

			this.sections.splice(index + 1, 0, duplicate);

			this.renderCanvas();
			this.selectSection(duplicate.id);
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Mueve sección arriba/abajo
         */
		moveSection: function (sectionId, direction) {
			this.saveToHistory();

			var index = this.getSectionIndex(sectionId);
			if (index === -1) {return;}

			var newIndex = index + direction;
			if (newIndex < 0 || newIndex >= this.sections.length) {return;}

			var section = this.sections.splice(index, 1)[0];
			this.sections.splice(newIndex, 0, section);

			this.renderCanvas();
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Obtiene índice de sección
         */
		getSectionIndex: function (sectionId) {
			for (var i = 0; i < this.sections.length; i++) {
				if (this.sections[i].id === sectionId) {
					return i;
				}
			}
			return -1;
		},

		/**
         * Selecciona sección
         */
		selectSection: function (sectionId) {
			this.selectedSection = sectionId;

			$('.flavor-canvas-section').removeClass('selected');
			$('.flavor-canvas-section[data-section-id="' + sectionId + '"]').addClass('selected');

			this.renderOptionsPanel();
			$('#flavor-editor-options').addClass('active');
		},

		/**
         * Deselecciona sección
         */
		deselectSection: function () {
			this.selectedSection = null;
			$('.flavor-canvas-section').removeClass('selected');
			$('#flavor-editor-options').removeClass('active');
		},

		/**
         * Renderiza el canvas
         */
		renderCanvas: function () {
			var $canvas = $('#flavor-editor-canvas');
			var $content = $canvas.find('.flavor-canvas-content');

			if (this.sections.length === 0) {
				$content.html('<div class="flavor-canvas-empty">' +
                    '<span class="dashicons dashicons-layout"></span>' +
                    '<p>Arrastra secciones aquí para empezar</p>' +
                '</div>');
				return;
			}

			var html = '';
			for (var i = 0; i < this.sections.length; i++) {
				html += this.renderCanvasSection(this.sections[i], i);
			}

			$content.html(html);
		},

		/**
         * Renderiza sección en canvas
         */
		renderCanvasSection: function (section, index) {
			var config = flavorLandingEditor.sections[section.type];
			if (!config) {return '';}

			var variantLabel = config.variants[section.variant] || section.variant;
			var isSelected = this.selectedSection === section.id ? ' selected' : '';

			var html = '<div class="flavor-canvas-section' + isSelected + '" ' +
                'data-section-id="' + section.id + '" ' +
                'data-section-type="' + section.type + '" ' +
                'draggable="true">';

			// Header
			html += '<div class="flavor-section-header">';
			html += '<span class="dashicons ' + config.icon + '"></span>';
			html += '<span class="flavor-section-title">' + config.label + '</span>';
			html += '<span class="flavor-section-variant">' + variantLabel + '</span>';
			html += '</div>';

			// Preview content
			html += '<div class="flavor-section-preview">';
			html += this.renderSectionPreview(section);
			html += '</div>';

			// Actions
			html += '<div class="flavor-section-actions">';
			html += '<button type="button" class="flavor-section-action flavor-section-action-up" title="' + flavorLandingEditor.i18n.moveUp + '">' +
                '<span class="dashicons dashicons-arrow-up-alt2"></span></button>';
			html += '<button type="button" class="flavor-section-action flavor-section-action-down" title="' + flavorLandingEditor.i18n.moveDown + '">' +
                '<span class="dashicons dashicons-arrow-down-alt2"></span></button>';
			html += '<button type="button" class="flavor-section-action flavor-section-action-duplicate" title="' + flavorLandingEditor.i18n.duplicate + '">' +
                '<span class="dashicons dashicons-admin-page"></span></button>';
			html += '<button type="button" class="flavor-section-action flavor-section-action-delete" title="Eliminar">' +
                '<span class="dashicons dashicons-trash"></span></button>';
			html += '</div>';

			html += '</div>';

			return html;
		},

		/**
         * Renderiza preview de sección con edición inline
         */
		renderSectionPreview: function (section) {
			var data = section.data || {};
			var html = '<div class="flavor-preview-content" data-section-id="' + section.id + '">';

			// Helper para crear elementos editables
			var editable = function (tag, field, content, maxLen) {
				var displayContent = content || '';
				if (maxLen && displayContent.length > maxLen) {
					displayContent = displayContent.substring(0, maxLen) + '...';
				}
				return '<' + tag + ' class="flavor-inline-editable" contenteditable="true" data-field="' + field + '" ' +
                    'title="Haz clic para editar">' +
                    displayContent +
                    '</' + tag + '>';
			};

			switch (section.type) {
				case 'hero':
					html += editable('h3', 'titulo', this.escapeHtml(data.titulo || 'Título principal'));
					html += editable('p', 'subtitulo', this.escapeHtml(data.subtitulo || 'Subtítulo aquí...'), 100);
					if (data.cta_texto) {
						html += '<span class="flavor-preview-btn flavor-inline-editable" contenteditable="true" data-field="cta_texto">' +
                            this.escapeHtml(data.cta_texto) + '</span>';
					}
					break;

				case 'features':
				case 'testimonios':
				case 'pricing':
				case 'faq':
				case 'equipo':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Título de sección'));
					if (data.items && data.items.length) {
						html += '<span class="flavor-preview-count">' + data.items.length + ' elementos</span>';
					}
					break;

				case 'cta':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Llamada a la acción'));
					html += '<span class="flavor-preview-btn flavor-inline-editable" contenteditable="true" data-field="boton_texto">' +
                        this.escapeHtml(data.boton_texto || 'Botón') + '</span>';
					break;

				case 'galeria':
				case 'logos':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Galería'));
					var images = data.imagenes || data.logos || [];
					if (images.length) {
						html += '<span class="flavor-preview-count">' + images.length + ' imágenes</span>';
					}
					break;

				case 'stats':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Estadísticas'));
					if (data.items && data.items.length) {
						html += '<div class="flavor-preview-stats">';
						data.items.slice(0, 4).forEach(function (item) {
							html += '<span>' + (item.numero || '0') + (item.sufijo || '') + '</span>';
						});
						html += '</div>';
					}
					break;

				case 'contacto':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Contacto'));
					html += '<span class="dashicons dashicons-email"></span>';
					if (data.email) {
						html += '<span class="flavor-inline-editable" contenteditable="true" data-field="email">' +
                            this.escapeHtml(data.email) + '</span>';
					}
					break;

				case 'video':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Video'));
					html += '<span class="dashicons dashicons-video-alt3"></span>';
					if (data.video_url) {
						html += '<span>Video configurado</span>';
					}
					break;

				case 'texto':
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Bloque de texto'));
					break;

				case 'separador':
					html += '<hr style="margin: 10px 0;">';
					break;

				default:
					html += editable('h4', 'titulo', this.escapeHtml(data.titulo || 'Sección'));
			}

			html += '</div>';
			return html;
		},

		/**
         * Campos de diseño comunes para todas las secciones
         */
		designFields: {
			padding_top: { type: 'range', label: 'Padding Superior', min: 0, max: 200, default: 60, unit: 'px', tab: 'design' },
			padding_bottom: { type: 'range', label: 'Padding Inferior', min: 0, max: 200, default: 60, unit: 'px', tab: 'design' },
			padding_left: { type: 'range', label: 'Padding Izquierdo', min: 0, max: 100, default: 20, unit: 'px', tab: 'design' },
			padding_right: { type: 'range', label: 'Padding Derecho', min: 0, max: 100, default: 20, unit: 'px', tab: 'design' },
			margin_top: { type: 'range', label: 'Margen Superior', min: 0, max: 100, default: 0, unit: 'px', tab: 'design' },
			margin_bottom: { type: 'range', label: 'Margen Inferior', min: 0, max: 100, default: 0, unit: 'px', tab: 'design' },
			border_radius: { type: 'range', label: 'Bordes Redondeados', min: 0, max: 50, default: 0, unit: 'px', tab: 'design' },
			max_width: { type: 'select', label: 'Ancho Máximo', options: { 'none': 'Sin límite', '1200px': '1200px', '1400px': '1400px', '960px': '960px', '100%': '100%' }, default: '1200px', tab: 'design' },
			text_align: { type: 'select', label: 'Alineación Texto', options: { 'left': 'Izquierda', 'center': 'Centro', 'right': 'Derecha' }, default: 'left', tab: 'design' },
			background_overlay: { type: 'range', label: 'Opacidad Overlay', min: 0, max: 100, default: 0, unit: '%', tab: 'design' },
			overlay_color: { type: 'color', label: 'Color Overlay', default: '#000000', tab: 'design' },
		},

		/**
         * Campos avanzados comunes
         */
		advancedFields: {
			css_class: { type: 'text', label: 'Clases CSS', placeholder: 'mi-clase otra-clase', tab: 'advanced' },
			css_id: { type: 'text', label: 'ID HTML', placeholder: 'mi-seccion', tab: 'advanced' },
			animation: { type: 'select', label: 'Animación', options: { 'none': 'Ninguna', 'fade-in': 'Fade In', 'slide-up': 'Deslizar Arriba', 'slide-left': 'Deslizar Izquierda', 'zoom-in': 'Zoom In' }, default: 'none', tab: 'advanced' },
			fullwidth: { type: 'checkbox', label: 'Ancho completo', default: false, tab: 'design' },
			visibility_desktop: { type: 'checkbox', label: 'Mostrar en Desktop', default: true, tab: 'advanced' },
			visibility_tablet: { type: 'checkbox', label: 'Mostrar en Tablet', default: true, tab: 'advanced' },
			visibility_mobile: { type: 'checkbox', label: 'Mostrar en Móvil', default: true, tab: 'advanced' },
		},

		/**
         * Tab activa actual
         */
		activeTab: 'content',

		/**
         * Renderiza panel de opciones con pestañas
         */
		renderOptionsPanel: function () {
			if (!this.selectedSection) {return;}

			var section = this.getSection(this.selectedSection);
			if (!section) {return;}

			var config = flavorLandingEditor.sections[section.type];
			if (!config) {return;}

			var $panel = $('#flavor-editor-options');
			var $content = $panel.find('.flavor-options-content');
			var self = this;

			var html = '';

			// Header con título
			html += '<div class="flavor-options-header">';
			html += '<span class="dashicons ' + config.icon + '"></span>';
			html += '<h3>' + config.label + '</h3>';
			html += '<button type="button" id="flavor-options-close" class="flavor-options-close">' +
                '<span class="dashicons dashicons-no-alt"></span></button>';
			html += '</div>';

			// Pestañas
			html += '<div class="flavor-options-tabs">';
			html += '<button type="button" class="flavor-tab' + (this.activeTab === 'content' ? ' active' : '') + '" data-tab="content">';
			html += '<span class="dashicons dashicons-edit"></span> Contenido</button>';
			html += '<button type="button" class="flavor-tab' + (this.activeTab === 'design' ? ' active' : '') + '" data-tab="design">';
			html += '<span class="dashicons dashicons-art"></span> Diseño</button>';
			html += '<button type="button" class="flavor-tab' + (this.activeTab === 'advanced' ? ' active' : '') + '" data-tab="advanced">';
			html += '<span class="dashicons dashicons-admin-generic"></span> Avanzado</button>';
			html += '</div>';

			// === TAB CONTENIDO ===
			html += '<div class="flavor-tab-content' + (this.activeTab === 'content' ? ' active' : '') + '" data-tab="content">';

			// Selector de variante con preview visual
			html += '<div class="flavor-options-section flavor-variant-section">';
			html += '<label class="flavor-field-label">Estilo de Sección</label>';
			html += '<div class="flavor-variant-grid">';
			for (var variantKey in config.variants) {
				var isSelected = section.variant === variantKey ? ' selected' : '';
				html += '<div class="flavor-variant-option' + isSelected + '" data-variant="' + variantKey + '">';
				html += '<div class="flavor-variant-preview flavor-variant-' + section.type + '-' + variantKey + '"></div>';
				html += '<span class="flavor-variant-label">' + config.variants[variantKey] + '</span>';
				html += '</div>';
			}
			html += '</div>';
			html += '</div>';

			// Campos de contenido
			html += '<div class="flavor-options-fields">';
			for (var fieldName in config.fields) {
				var field = config.fields[fieldName];
				var value = section.data[fieldName];

				// Verificar condiciones
				if (field.condition) {
					var conditionMet = true;
					for (var condKey in field.condition) {
						if (condKey === 'variant' && section.variant !== field.condition[condKey]) {
							conditionMet = false;
						}
					}
					if (!conditionMet) {continue;}
				}

				// Solo campos de contenido (excluir los que empiezan con color_)
				if (fieldName.indexOf('color_') === 0) {continue;}

				html += this.renderField(fieldName, field, value);
			}

			// Campos de color al final del contenido
			html += '<div class="flavor-color-fields">';
			html += '<label class="flavor-field-label flavor-section-label">Colores</label>';
			for (var fieldName in config.fields) {
				var field = config.fields[fieldName];
				if (fieldName.indexOf('color_') === 0) {
					var value = section.data[fieldName];
					html += this.renderField(fieldName, field, value);
				}
			}
			html += '</div>';
			html += '</div>';
			html += '</div>'; // fin tab contenido

			// === TAB DISEÑO ===
			html += '<div class="flavor-tab-content' + (this.activeTab === 'design' ? ' active' : '') + '" data-tab="design">';
			html += '<div class="flavor-options-fields">';

			// Asegurar que existan los objetos design y advanced
			var design = section.design || {};
			var advanced = section.advanced || {};

			// Spacing
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-editor-expand"></span> Espaciado</label>';
			html += this.renderField('padding_top', this.designFields.padding_top, design.padding_top);
			html += this.renderField('padding_bottom', this.designFields.padding_bottom, design.padding_bottom);
			html += '<div class="flavor-field-row">';
			html += this.renderField('padding_left', this.designFields.padding_left, design.padding_left);
			html += this.renderField('padding_right', this.designFields.padding_right, design.padding_right);
			html += '</div>';
			html += '</div>';

			// Márgenes
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-align-center"></span> Márgenes</label>';
			html += '<div class="flavor-field-row">';
			html += this.renderField('margin_top', this.designFields.margin_top, design.margin_top);
			html += this.renderField('margin_bottom', this.designFields.margin_bottom, design.margin_bottom);
			html += '</div>';
			html += '</div>';

			// Apariencia
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-admin-appearance"></span> Apariencia</label>';
			html += this.renderField('border_radius', this.designFields.border_radius, design.border_radius);
			html += this.renderField('max_width', this.designFields.max_width, design.max_width);
			html += this.renderField('fullwidth', this.advancedFields.fullwidth, !!advanced.fullwidth);
			html += this.renderField('text_align', this.designFields.text_align, design.text_align);
			html += '</div>';

			// Overlay
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-format-image"></span> Overlay de Fondo</label>';
			html += this.renderField('background_overlay', this.designFields.background_overlay, design.background_overlay);
			html += this.renderField('overlay_color', this.designFields.overlay_color, design.overlay_color);
			html += '</div>';

			html += '</div>';
			html += '</div>'; // fin tab diseño

			// === TAB AVANZADO ===
			html += '<div class="flavor-tab-content' + (this.activeTab === 'advanced' ? ' active' : '') + '" data-tab="advanced">';
			html += '<div class="flavor-options-fields">';

			// Atributos HTML
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-editor-code"></span> Atributos HTML</label>';
			html += this.renderField('css_class', this.advancedFields.css_class, advanced.css_class);
			html += this.renderField('css_id', this.advancedFields.css_id, advanced.css_id);
			html += '</div>';

			// Animación
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-video-alt3"></span> Animación</label>';
			html += this.renderField('animation', this.advancedFields.animation, advanced.animation);
			html += '</div>';

			// Visibilidad responsiva
			html += '<div class="flavor-design-group">';
			html += '<label class="flavor-field-label flavor-section-label"><span class="dashicons dashicons-visibility"></span> Visibilidad por Dispositivo</label>';
			html += '<div class="flavor-visibility-options">';
			html += this.renderField('visibility_desktop', this.advancedFields.visibility_desktop, advanced.visibility_desktop !== false);
			html += this.renderField('visibility_tablet', this.advancedFields.visibility_tablet, advanced.visibility_tablet !== false);
			html += this.renderField('visibility_mobile', this.advancedFields.visibility_mobile, advanced.visibility_mobile !== false);
			html += '</div>';
			html += '</div>';

			html += '</div>';
			html += '</div>'; // fin tab avanzado

			$content.html(html);

			// Inicializar color pickers en panel
			this.initColorPickers();

			// Bind eventos de pestañas
			$content.find('.flavor-tab').off('click').on('click', function () {
				var tab = $(this).data('tab');
				self.activeTab = tab;
				$content.find('.flavor-tab').removeClass('active');
				$(this).addClass('active');
				$content.find('.flavor-tab-content').removeClass('active');
				$content.find('.flavor-tab-content[data-tab="' + tab + '"]').addClass('active');
			});

			// Bind eventos de variantes visuales
			$content.find('.flavor-variant-option').off('click').on('click', function () {
				var variant = $(this).data('variant');
				$content.find('.flavor-variant-option').removeClass('selected');
				$(this).addClass('selected');
				self.updateSectionVariant(variant);
			});

			// Bind eventos de campos de diseño
			$content.find('[data-tab="design"] .flavor-field-input, [data-tab="design"] .flavor-field-select, [data-tab="design"] .flavor-range-input, [data-tab="design"] .flavor-field-checkbox').off('change input').on('change input', function () {
				var name = $(this).attr('name');
				var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();

				// Actualizar valor mostrado del range
				if ($(this).hasClass('flavor-range-input')) {
					var unit = $(this).closest('.flavor-field').find('.flavor-range-value').data('unit') || '';
					$(this).siblings('.flavor-range-value').text(value + unit);
				}

				if (name === 'fullwidth') {
					if (value) {
						var $maxWidth = $content.find('[name="max_width"]');
						if ($maxWidth.length) {
							$maxWidth.val('100%');
						}
						self.updateSectionDesign('max_width', '100%');
					}
					self.updateSectionAdvanced(name, value);
					return;
				}

				self.updateSectionDesign(name, value);
			});

			// Bind eventos de campos avanzados
			$content.find('[data-tab="advanced"] .flavor-field-input, [data-tab="advanced"] .flavor-field-select, [data-tab="advanced"] .flavor-field-checkbox').off('change').on('change', function () {
				var name = $(this).attr('name');
				var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
				self.updateSectionAdvanced(name, value);
			});
		},

		/**
         * Actualiza datos de diseño de la sección
         */
		updateSectionDesign: function (field, value) {
			var section = this.getSection(this.selectedSection);
			if (!section) {return;}

			if (!section.design) {section.design = {};}
			section.design[field] = value;

			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Actualiza datos avanzados de la sección
         */
		updateSectionAdvanced: function (field, value) {
			var section = this.getSection(this.selectedSection);
			if (!section) {return;}

			if (!section.advanced) {section.advanced = {};}
			section.advanced[field] = value;

			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Actualiza campo desde edición inline
         */
		updateInlineField: function (sectionId, field, value) {
			var section = this.getSection(sectionId);
			if (!section) {return;}

			// Solo guardar si el valor cambió
			var oldValue = section.data[field] || '';
			if (oldValue === value) {return;}

			// Guardar historial antes del cambio
			this.saveToHistory();

			// Actualizar el dato
			section.data[field] = value;
			this.isDirty = true;

			// Si el panel de opciones está abierto para esta sección, actualizar el campo
			if (this.selectedSection === sectionId) {
				var $input = $('#flavor-editor-options [name="' + field + '"]');
				if ($input.length) {
					if ($input.is('textarea')) {
						$input.val(value);
					} else {
						$input.val(value);
					}
				}
			}

			// Refrescar preview del iframe
			this.refreshPreview();

			// Mostrar indicador de guardado
			this.showNotice('Campo actualizado', 'success', 1500);
		},

		/**
         * Renderiza un campo
         */
		renderField: function (name, field, value) {
			var html = '<div class="flavor-field flavor-field-' + field.type + '" data-field-name="' + name + '">';
			html += '<label class="flavor-field-label">' + field.label + '</label>';

			switch (field.type) {
				case 'text':
				case 'url':
				case 'email':
					html += '<input type="' + field.type + '" name="' + name + '" value="' + this.escapeHtml(value || '') + '" class="flavor-field-input">';
					break;

				case 'number':
					var min = field.min !== undefined ? ' min="' + field.min + '"' : '';
					var max = field.max !== undefined ? ' max="' + field.max + '"' : '';
					html += '<input type="number" name="' + name + '" value="' + (value || field.default || 0) + '"' + min + max + ' class="flavor-field-input">';
					break;

				case 'textarea':
					html += '<textarea name="' + name + '" class="flavor-field-textarea" rows="3">' + this.escapeHtml(value || '') + '</textarea>';
					break;

				case 'wysiwyg':
					html += '<textarea name="' + name + '" class="flavor-field-wysiwyg" rows="6">' + this.escapeHtml(value || '') + '</textarea>';
					break;

				case 'select':
					html += '<select name="' + name + '" class="flavor-field-select">';
					for (var optKey in field.options) {
						var selected = value === optKey ? ' selected' : '';
						html += '<option value="' + optKey + '"' + selected + '>' + field.options[optKey] + '</option>';
					}
					html += '</select>';
					break;

				case 'checkbox':
					var checked = value ? ' checked' : '';
					html += '<label class="flavor-checkbox-label">';
					html += '<input type="checkbox" name="' + name + '"' + checked + ' class="flavor-field-checkbox">';
					html += '<span class="flavor-checkbox-text">' + field.label + '</span>';
					html += '</label>';
					break;

				case 'color':
					html += '<input type="text" name="' + name + '" value="' + (value || field.default || '#000000') + '" class="flavor-color-picker">';
					break;

				case 'image':
					html += '<div class="flavor-image-field">';
					if (value) {
						html += '<div class="flavor-image-preview">';
						html += '<img src="' + value + '" alt="">';
						html += '<button type="button" class="flavor-image-remove"><span class="dashicons dashicons-no-alt"></span></button>';
						html += '</div>';
					}
					html += '<input type="hidden" name="' + name + '" value="' + (value || '') + '" class="flavor-image-value">';
					html += '<button type="button" class="button flavor-image-select">' + flavorLandingEditor.i18n.selectImage + '</button>';
					html += '</div>';
					break;

				case 'gallery':
					html += '<div class="flavor-gallery-field">';
					html += '<div class="flavor-gallery-preview">';
					if (value && value.length) {
						value.forEach(function (img) {
							var imgUrl = typeof img === 'object' ? img.url : img;
							html += '<div class="flavor-gallery-thumb"><img src="' + imgUrl + '" alt=""></div>';
						});
					}
					html += '</div>';
					html += '<input type="hidden" name="' + name + '" value=\'' + JSON.stringify(value || []) + '\' class="flavor-gallery-value">';
					html += '<button type="button" class="button flavor-gallery-select">' + flavorLandingEditor.i18n.selectImages + '</button>';
					html += '</div>';
					break;

				case 'icon':
					html += '<div class="flavor-icon-field">';
					html += '<button type="button" class="flavor-icon-trigger">';
					if (value) {
						html += '<span class="dashicons dashicons-' + value + '"></span>';
					} else {
						html += '<span class="dashicons dashicons-plus"></span>';
					}
					html += '</button>';
					html += '<input type="hidden" name="' + name + '" value="' + (value || '') + '" class="flavor-icon-value">';
					html += this.renderIconPicker();
					html += '</div>';
					break;

				case 'repeater':
					html += this.renderRepeaterField(name, field, value);
					break;

				case 'range':
					var rangeValue = value !== undefined && value !== null ? value : (field.default || 0);
					var rangeMin = field.min !== undefined ? field.min : 0;
					var rangeMax = field.max !== undefined ? field.max : 100;
					var rangeUnit = field.unit || '';
					html += '<div class="flavor-range-wrapper">';
					html += '<input type="range" name="' + name + '" value="' + rangeValue + '" min="' + rangeMin + '" max="' + rangeMax + '" class="flavor-range-input">';
					html += '<span class="flavor-range-value" data-unit="' + rangeUnit + '">' + rangeValue + rangeUnit + '</span>';
					html += '</div>';
					break;
			}

			html += '</div>';
			return html;
		},

		/**
         * Renderiza campo repeater
         */
		renderRepeaterField: function (name, field, value) {
			var self = this;
			var items = value || [];

			var html = '<div class="flavor-field-repeater" data-field-name="' + name + '">';
			html += '<div class="flavor-repeater-items">';

			items.forEach(function (item, index) {
				html += self.renderRepeaterItem(field.fields, item, index);
			});

			html += '</div>';
			html += '<button type="button" class="button flavor-repeater-add">';
			html += '<span class="dashicons dashicons-plus"></span> ' + flavorLandingEditor.i18n.addItem;
			html += '</button>';
			html += '</div>';

			return html;
		},

		/**
         * Renderiza item de repeater
         */
		renderRepeaterItem: function (fields, item, index) {
			var html = '<div class="flavor-repeater-item" data-index="' + index + '">';
			html += '<div class="flavor-repeater-item-header">';
			html += '<span class="flavor-repeater-item-title">Elemento ' + (index + 1) + '</span>';
			html += '<button type="button" class="flavor-repeater-remove"><span class="dashicons dashicons-trash"></span></button>';
			html += '</div>';
			html += '<div class="flavor-repeater-item-fields">';

			for (var fieldName in fields) {
				var field = fields[fieldName];
				var value = item[fieldName] || '';

				html += '<div class="flavor-repeater-field" data-subfield-name="' + fieldName + '">';
				html += '<label>' + field.label + '</label>';

				switch (field.type) {
					case 'text':
					case 'url':
						html += '<input type="' + field.type + '" value="' + this.escapeHtml(value) + '" class="flavor-field-input">';
						break;
					case 'textarea':
						html += '<textarea class="flavor-field-textarea" rows="2">' + this.escapeHtml(value) + '</textarea>';
						break;
					case 'number':
						html += '<input type="number" value="' + (value || 0) + '" class="flavor-field-input">';
						break;
					case 'checkbox':
						var checked = value ? ' checked' : '';
						html += '<input type="checkbox"' + checked + ' class="flavor-field-checkbox">';
						break;
					case 'image':
						html += '<div class="flavor-image-field-mini">';
						if (value) {
							html += '<img src="' + value + '" alt="" class="flavor-mini-preview">';
						}
						html += '<input type="hidden" value="' + (value || '') + '" class="flavor-image-value">';
						html += '<button type="button" class="button button-small flavor-image-select">Imagen</button>';
						if (value) {
							html += '<button type="button" class="button button-small flavor-image-remove">X</button>';
						}
						html += '</div>';
						break;
					case 'icon':
						html += '<div class="flavor-icon-field-mini">';
						html += '<button type="button" class="flavor-icon-trigger">';
						if (value) {
							html += '<span class="dashicons dashicons-' + value + '"></span>';
						} else {
							html += '<span class="dashicons dashicons-plus"></span>';
						}
						html += '</button>';
						html += '<input type="hidden" value="' + (value || '') + '" class="flavor-icon-value">';
						html += this.renderIconPicker();
						html += '</div>';
						break;
				}

				html += '</div>';
			}

			html += '</div>';
			html += '</div>';

			return html;
		},

		/**
         * Renderiza selector de iconos
         */
		renderIconPicker: function () {
			var html = '<div class="flavor-icon-picker">';
			html += '<input type="text" class="flavor-icon-search" placeholder="Buscar icono...">';
			html += '<div class="flavor-icon-grid">';

			flavorLandingEditor.icons.forEach(function (icon) {
				html += '<button type="button" class="flavor-icon-option" data-icon="' + icon + '" title="' + icon + '">';
				html += '<span class="dashicons dashicons-' + icon + '"></span>';
				html += '</button>';
			});

			html += '</div>';
			html += '</div>';

			return html;
		},

		/**
         * Añade item a repeater
         */
		addRepeaterItem: function ($repeater) {
			var fieldName = $repeater.data('field-name');
			var section = this.getSection(this.selectedSection);
			var config = flavorLandingEditor.sections[section.type];
			var fieldConfig = config.fields[fieldName];

			// Crear item vacío
			var newItem = {};
			for (var subFieldName in fieldConfig.fields) {
				newItem[subFieldName] = '';
			}

			// Añadir a datos
			if (!section.data[fieldName]) {
				section.data[fieldName] = [];
			}
			section.data[fieldName].push(newItem);

			// Re-renderizar panel
			this.renderOptionsPanel();
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Elimina item de repeater
         */
		removeRepeaterItem: function ($item) {
			var $repeater = $item.closest('.flavor-field-repeater');
			var fieldName = $repeater.data('field-name');
			var index = $item.data('index');

			var section = this.getSection(this.selectedSection);
			if (section.data[fieldName]) {
				section.data[fieldName].splice(index, 1);
			}

			this.renderOptionsPanel();
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Actualiza datos de sección
         */
		updateSectionData: function ($input) {
			if (!this.selectedSection) {return;}

			var section = this.getSection(this.selectedSection);
			if (!section) {return;}

			var $field = $input.closest('.flavor-field, .flavor-repeater-field');
			var fieldName = $field.data('field-name') || $field.data('subfield-name');

			// Es un campo dentro de repeater?
			var $repeaterItem = $input.closest('.flavor-repeater-item');
			if ($repeaterItem.length) {
				var $repeater = $repeaterItem.closest('.flavor-field-repeater');
				var repeaterName = $repeater.data('field-name');
				var itemIndex = $repeaterItem.data('index');
				var subFieldName = $field.data('subfield-name');

				if (!section.data[repeaterName]) {
					section.data[repeaterName] = [];
				}
				if (!section.data[repeaterName][itemIndex]) {
					section.data[repeaterName][itemIndex] = {};
				}

				var value = $input.is(':checkbox') ? $input.is(':checked') : $input.val();
				section.data[repeaterName][itemIndex][subFieldName] = value;
			} else {
				// Campo normal
				var value = $input.is(':checkbox') ? $input.is(':checked') : $input.val();
				section.data[fieldName] = value;
			}

			this.renderCanvas();
			this.isDirty = true;

			// Debounce preview refresh
			clearTimeout(this.previewTimer);
			this.previewTimer = setTimeout(function () {
				FlavorLandingEditor.Editor.refreshPreview();
			}, 500);
		},

		/**
         * Actualiza variante de sección
         */
		updateSectionVariant: function (variant) {
			if (!this.selectedSection) {return;}

			var section = this.getSection(this.selectedSection);
			if (!section) {return;}

			section.variant = variant;

			this.renderCanvas();
			this.renderOptionsPanel();
			this.refreshPreview();
			this.isDirty = true;
		},

		/**
         * Abre librería de medios
         */
		openMediaLibrary: function ($button) {
			var self = this;
			var $field = $button.closest('.flavor-image-field, .flavor-image-field-mini');
			var $input = $field.find('.flavor-image-value');

			var frame = wp.media({
				title: flavorLandingEditor.i18n.selectImage,
				multiple: false,
				library: { type: 'image' }
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				$input.val(attachment.url).trigger('change');

				// Actualizar preview
				var $preview = $field.find('.flavor-image-preview, .flavor-mini-preview');
				if ($preview.length) {
					if ($preview.is('img')) {
						$preview.attr('src', attachment.url);
					} else {
						$preview.find('img').attr('src', attachment.url);
					}
				} else {
					if ($field.hasClass('flavor-image-field-mini')) {
						$field.prepend('<img src="' + attachment.url + '" class="flavor-mini-preview">');
					} else {
						$button.before('<div class="flavor-image-preview"><img src="' + attachment.url + '"><button type="button" class="flavor-image-remove"><span class="dashicons dashicons-no-alt"></span></button></div>');
					}
				}

				self.updateSectionData($input);
			});

			frame.open();
		},

		/**
         * Elimina imagen
         */
		removeImage: function ($button) {
			var $field = $button.closest('.flavor-image-field, .flavor-image-field-mini');
			var $input = $field.find('.flavor-image-value');

			$input.val('').trigger('change');
			$field.find('.flavor-image-preview, .flavor-mini-preview').remove();

			this.updateSectionData($input);
		},

		/**
         * Abre selector de galería
         */
		openGallerySelector: function ($button) {
			var self = this;
			var $field = $button.closest('.flavor-gallery-field');
			var $input = $field.find('.flavor-gallery-value');

			var frame = wp.media({
				title: flavorLandingEditor.i18n.selectImages,
				multiple: true,
				library: { type: 'image' }
			});

			frame.on('select', function () {
				var attachments = frame.state().get('selection').toJSON();
				var images = attachments.map(function (att) {
					return { id: att.id, url: att.url };
				});

				$input.val(JSON.stringify(images)).trigger('change');

				// Actualizar preview
				var $preview = $field.find('.flavor-gallery-preview');
				$preview.empty();
				images.forEach(function (img) {
					$preview.append('<div class="flavor-gallery-thumb"><img src="' + img.url + '"></div>');
				});

				self.updateSectionData($input);
			});

			frame.open();
		},

		/**
         * Abre selector de icono
         */
		openIconPicker: function ($trigger) {
			var $picker = $trigger.siblings('.flavor-icon-picker');
			$('.flavor-icon-picker').not($picker).removeClass('active');
			$picker.toggleClass('active');
		},

		/**
         * Selecciona icono
         */
		selectIcon: function ($option) {
			var icon = $option.data('icon');
			var $field = $option.closest('.flavor-icon-field, .flavor-icon-field-mini');
			var $input = $field.find('.flavor-icon-value');
			var $trigger = $field.find('.flavor-icon-trigger');

			$input.val(icon).trigger('change');
			$trigger.html('<span class="dashicons dashicons-' + icon + '"></span>');
			$field.find('.flavor-icon-picker').removeClass('active');

			this.updateSectionData($input);
		},

		/**
         * Obtiene sección por ID
         */
		getSection: function (sectionId) {
			for (var i = 0; i < this.sections.length; i++) {
				if (this.sections[i].id === sectionId) {
					return this.sections[i];
				}
			}
			return null;
		},

		/**
         * Carga estructura desde servidor
         */
		loadStructure: function () {
			var self = this;
			var postId = flavorLandingEditor.postId;

			if (!postId) {return;}

			$.ajax({
				url: flavorLandingEditor.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_landing_load',
					nonce: flavorLandingEditor.nonce,
					post_id: postId
				},
				success: function (response) {
					if (response.success && response.data.structure) {
						self.sections = response.data.structure.sections || [];
						self.renderCanvas();
						self.refreshPreview();
					}
				}
			});
		},

		/**
         * Guarda estructura
         */
		save: function () {
			var self = this;
			var $btn = $('#flavor-editor-save');
			var originalText = $btn.text();

			$btn.text(flavorLandingEditor.i18n.saving).prop('disabled', true);

			$.ajax({
				url: flavorLandingEditor.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_landing_save',
					nonce: flavorLandingEditor.nonce,
					post_id: flavorLandingEditor.postId,
					sections: JSON.stringify(self.sections),
					color_primario: $('#flavor-global-color').val()
				},
				success: function (response) {
					if (response.success) {
						$btn.text(flavorLandingEditor.i18n.saved);
						self.isDirty = false;
						self.showNotice('success', response.data.message);
					} else {
						$btn.text(flavorLandingEditor.i18n.error);
						self.showNotice('error', response.data.message);
					}
				},
				error: function () {
					$btn.text(flavorLandingEditor.i18n.error);
					self.showNotice('error', 'Error de conexión');
				},
				complete: function () {
					setTimeout(function () {
						$btn.text(originalText).prop('disabled', false);
					}, 2000);
				}
			});
		},

		/**
         * Refresca preview
         */
		refreshPreview: function () {
			var self = this;
			var $iframe = $('#flavor-preview-iframe');

			if (!$iframe.length) {return;}

			// Construir form para POST
			var form = document.createElement('form');
			form.method = 'POST';
			form.action = flavorLandingEditor.previewUrl + '&nonce=' + flavorLandingEditor.nonce;
			form.target = 'flavor-preview-frame';
			form.style.display = 'none';

			var sectionsInput = document.createElement('input');
			sectionsInput.type = 'hidden';
			sectionsInput.name = 'sections';
			sectionsInput.value = JSON.stringify(this.sections);
			form.appendChild(sectionsInput);

			var colorInput = document.createElement('input');
			colorInput.type = 'hidden';
			colorInput.name = 'color_primario';
			colorInput.value = $('#flavor-global-color').val() || '#3b82f6';
			form.appendChild(colorInput);

			var nonceInput = document.createElement('input');
			nonceInput.type = 'hidden';
			nonceInput.name = 'nonce';
			nonceInput.value = flavorLandingEditor.nonce;
			form.appendChild(nonceInput);

			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
		},

		/**
         * Cambia dispositivo de preview
         */
		setPreviewDevice: function (device) {
			var $wrapper = $('.flavor-preview-wrapper');
			$wrapper.removeClass('device-desktop device-tablet device-mobile');
			$wrapper.addClass('device-' + device);
		},

		/**
         * Inicializa color pickers
         */
		initColorPickers: function () {
			$('.flavor-color-picker').each(function () {
				if (!$(this).hasClass('wp-color-picker')) {
					$(this).wpColorPicker({
						change: function (event, ui) {
							$(event.target).val(ui.color.toString()).trigger('change');
						}
					});
				}
			});
		},

		/**
         * Actualiza color global
         */
		updateGlobalColor: function (color) {
			this.isDirty = true;
			this.refreshPreview();
		},

		/**
         * Filtra secciones por búsqueda
         */
		filterSections: function () {
			var query = (this.currentSearchTerm || '').toLowerCase();
			var category = this.currentSectionCategory || 'all';

			$('.flavor-editor-section-item').each(function () {
				var $item = $(this);
				var label = $item.find('.flavor-section-item-label').text().toLowerCase();
				var desc = $item.find('.flavor-section-item-desc').text().toLowerCase();
				var matchesText = query.length === 0 || label.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
				var itemCategory = $item.data('category') || 'general';
				var matchesCategory = category === 'all' || itemCategory === category;
				$item.toggle(matchesText && matchesCategory);
			});
		},

		/**
         * Guarda estado en historial (sistema mejorado con redo)
         */
		saveToHistory: function () {
			// Si estamos en medio del historial, eliminar los estados "futuros"
			if (this.historyIndex < this.historyStack.length - 1) {
				this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
			}

			// Guardar snapshot actual
			var snapshot = JSON.parse(JSON.stringify(this.sections));
			this.historyStack.push(snapshot);

			// Limitar tamaño del historial
			if (this.historyStack.length > this.maxHistory) {
				this.historyStack.shift();
			} else {
				this.historyIndex++;
			}

			// Asegurar que el índice sea válido
			this.historyIndex = this.historyStack.length - 1;

			this.updateUndoRedoButtons();
		},

		/**
         * Deshace último cambio (Ctrl+Z)
         */
		undo: function () {
			if (this.historyIndex <= 0) {
				this.showNotice('info', flavorLandingEditor.i18n.noHistory || 'No hay más cambios para deshacer');
				return;
			}

			this.historyIndex--;
			this.sections = JSON.parse(JSON.stringify(this.historyStack[this.historyIndex]));
			this.renderCanvas();
			this.deselectSection();
			this.refreshPreview();
			this.isDirty = true;

			this.updateUndoRedoButtons();
			this.showNotice('success', flavorLandingEditor.i18n.undone || 'Cambio deshecho');
		},

		/**
         * Rehace cambio deshecho (Ctrl+Y)
         */
		redo: function () {
			if (this.historyIndex >= this.historyStack.length - 1) {
				this.showNotice('info', flavorLandingEditor.i18n.noRedo || 'No hay más cambios para rehacer');
				return;
			}

			this.historyIndex++;
			this.sections = JSON.parse(JSON.stringify(this.historyStack[this.historyIndex]));
			this.renderCanvas();
			this.deselectSection();
			this.refreshPreview();
			this.isDirty = true;

			this.updateUndoRedoButtons();
			this.showNotice('success', flavorLandingEditor.i18n.redone || 'Cambio rehecho');
		},

		/**
         * Actualiza estado de botones undo/redo
         */
		updateUndoRedoButtons: function () {
			var canUndo = this.historyIndex > 0;
			var canRedo = this.historyIndex < this.historyStack.length - 1;

			$('#flavor-editor-undo').prop('disabled', !canUndo).toggleClass('disabled', !canUndo);
			$('#flavor-editor-redo').prop('disabled', !canRedo).toggleClass('disabled', !canRedo);
		},

		/**
         * Inicia autosave
         */
		startAutosave: function () {
			var self = this;

			this.autosaveTimer = setInterval(function () {
				if (self.isDirty && self.sections.length > 0) {
					self.autosave();
				}
			}, 30000); // 30 segundos
		},

		/**
         * Autosave
         */
		autosave: function () {
			$.ajax({
				url: flavorLandingEditor.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_landing_autosave',
					nonce: flavorLandingEditor.nonce,
					post_id: flavorLandingEditor.postId,
					sections: JSON.stringify(this.sections),
					color_primario: $('#flavor-global-color').val()
				}
			});
		},

		/**
         * Atajos de teclado
         */
		initKeyboardShortcuts: function () {
			var self = this;

			$(document).on('keydown', function (e) {
				// Ctrl+S para guardar
				if ((e.ctrlKey || e.metaKey) && e.key === 's') {
					e.preventDefault();
					self.save();
				}

				// Ctrl+Z para deshacer
				if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
					e.preventDefault();
					self.undo();
				}

				// Ctrl+Y o Ctrl+Shift+Z para rehacer
				if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
					e.preventDefault();
					self.redo();
				}

				// Delete para eliminar sección seleccionada
				if (e.key === 'Delete' && self.selectedSection && !$(e.target).is('input, textarea')) {
					self.deleteSection(self.selectedSection);
				}

				// Escape para deseleccionar
				if (e.key === 'Escape') {
					self.deselectSection();
					self.closeTemplatesModal();
				}

				// Ctrl++ y Ctrl+- para zoom
				if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
					e.preventDefault();
					self.zoomIn();
				}
				if ((e.ctrlKey || e.metaKey) && e.key === '-') {
					e.preventDefault();
					self.zoomOut();
				}
				if ((e.ctrlKey || e.metaKey) && e.key === '0') {
					e.preventDefault();
					self.setZoom(100);
				}
			});
		},

		/**
         * Muestra notificación
         */
		showNotice: function (type, message) {
			var $notice = $('<div class="flavor-notice flavor-notice-' + type + '">' + message + '</div>');
			$('.flavor-editor-notices').append($notice);

			setTimeout(function () {
				$notice.fadeOut(300, function () {
					$(this).remove();
				});
			}, 3000);
		},

		/**
         * Escapa HTML
         */
		escapeHtml: function (text) {
			if (!text) {return '';}
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		// =========================================
		// SISTEMA DE ZOOM
		// =========================================

		/**
         * Inicializa el sistema de zoom
         */
		initZoom: function () {
			this.setZoom(this.zoomLevel);
		},

		/**
         * Establece el nivel de zoom
         */
		setZoom: function (level) {
			// Asegurar que el nivel está dentro de los límites
			level = Math.max(this.zoomLevels[0], Math.min(level, this.zoomLevels[this.zoomLevels.length - 1]));

			this.zoomLevel = level;

			// Aplicar transformación al área de preview (iframe)
			var $previewWrapper = $('.flavor-preview-wrapper');
			var $iframe = $('#flavor-preview-iframe');

			$previewWrapper.css({
				'transform': 'scale(' + (level / 100) + ')',
				'transform-origin': 'top center'
			});

			// Actualizar UI
			$('#flavor-zoom-value').text(level + '%');
			$('#flavor-zoom-select').val(level);

			// Actualizar clases para ajustes de diseño
			$previewWrapper.removeClass('zoom-50 zoom-75 zoom-100 zoom-125 zoom-150');
			$previewWrapper.addClass('zoom-' + level);
		},

		/**
         * Aumenta el zoom
         */
		zoomIn: function () {
			var currentIndex = this.zoomLevels.indexOf(this.zoomLevel);
			if (currentIndex < this.zoomLevels.length - 1) {
				this.setZoom(this.zoomLevels[currentIndex + 1]);
			}
		},

		/**
         * Reduce el zoom
         */
		zoomOut: function () {
			var currentIndex = this.zoomLevels.indexOf(this.zoomLevel);
			if (currentIndex > 0) {
				this.setZoom(this.zoomLevels[currentIndex - 1]);
			}
		},

		// =========================================
		// DEVICE PREVIEW
		// =========================================

		/**
         * Inicializa device preview
         */
		initDevicePreview: function () {
			this.setDevice(this.currentDevice);
		},

		/**
         * Cambia el dispositivo de preview
         */
		setDevice: function (device) {
			this.currentDevice = device;

			var width = this.deviceWidths[device];
			var $canvas = $('#flavor-editor-canvas');
			var $previewWrapper = $('.flavor-preview-wrapper');
			var $iframe = $('#flavor-preview-iframe');

			// Actualizar canvas
			$canvas.css('max-width', width);
			$canvas.removeClass('viewport-desktop viewport-tablet viewport-mobile');
			$canvas.addClass('viewport-' + device);

			// Actualizar preview iframe
			$previewWrapper.removeClass('device-desktop device-tablet device-mobile');
			$previewWrapper.addClass('device-' + device);

			// Actualizar botones en ambas ubicaciones (header y toolbar)
			$('.flavor-preview-device, .flavor-device-btn').removeClass('active');
			$('.flavor-preview-device[data-device="' + device + '"], .flavor-device-btn[data-device="' + device + '"]').addClass('active');
		},

		// =========================================
		// SISTEMA DE PLANTILLAS
		// =========================================

		/**
         * Carga las plantillas desde el servidor
         */
		loadTemplates: function () {
			var self = this;

			if (this.templatesLoaded) {return;}

			$.ajax({
				url: flavorLandingEditor.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_landing_get_templates',
					nonce: flavorLandingEditor.nonce
				},
				success: function (response) {
					if (response.success && response.data.templates) {
						self.templates = response.data.templates;
						self.templatesLoaded = true;
					}
				}
			});
		},

		/**
         * Abre el modal de plantillas
         */
		openTemplatesModal: function () {
			var self = this;

			// Crear modal si no existe
			if ($('#flavor-templates-modal').length === 0) {
				this.createTemplatesModal();
			}

			// Renderizar plantillas
			this.renderTemplatesGrid();

			// Mostrar modal
			$('#flavor-templates-modal').addClass('active');
			$('body').addClass('flavor-modal-open');
		},

		/**
         * Crea el HTML del modal de plantillas
         */
		createTemplatesModal: function () {
			var modalHtml = '<div id="flavor-templates-modal" class="flavor-templates-modal">' +
                '<div class="flavor-templates-modal-overlay"></div>' +
                '<div class="flavor-templates-modal-content">' +
                    '<div class="flavor-templates-modal-header">' +
                        '<h2>' + (flavorLandingEditor.i18n.templates || 'Plantillas') + '</h2>' +
                        '<button type="button" class="flavor-templates-modal-close">' +
                            '<span class="dashicons dashicons-no-alt"></span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="flavor-templates-modal-categories">' +
                        '<button type="button" class="flavor-template-category-btn active" data-category="all">' +
                            (flavorLandingEditor.i18n.allTemplates || 'Todas') +
                        '</button>' +
                        '<button type="button" class="flavor-template-category-btn" data-category="negocio">' +
                            (flavorLandingEditor.i18n.business || 'Negocio') +
                        '</button>' +
                        '<button type="button" class="flavor-template-category-btn" data-category="portfolio">' +
                            (flavorLandingEditor.i18n.portfolio || 'Portfolio') +
                        '</button>' +
                        '<button type="button" class="flavor-template-category-btn" data-category="app">' +
                            (flavorLandingEditor.i18n.app || 'App') +
                        '</button>' +
                        '<button type="button" class="flavor-template-category-btn" data-category="servicios">' +
                            (flavorLandingEditor.i18n.services || 'Servicios') +
                        '</button>' +
                    '</div>' +
                    '<div class="flavor-templates-modal-body">' +
                        '<div class="flavor-templates-grid"></div>' +
                    '</div>' +
                '</div>' +
            '</div>';

			$('body').append(modalHtml);
		},

		/**
         * Renderiza la grid de plantillas
         */
		renderTemplatesGrid: function (category) {
			var self = this;
			var $grid = $('.flavor-templates-grid');
			category = category || 'all';

			var html = '';

			// Plantilla en blanco siempre primero
			html += '<div class="flavor-template-card flavor-template-blank" data-template-id="blank">' +
                '<div class="flavor-template-preview">' +
                    '<span class="dashicons dashicons-plus-alt2"></span>' +
                '</div>' +
                '<div class="flavor-template-info">' +
                    '<h4>' + (flavorLandingEditor.i18n.blankTemplate || 'Página en Blanco') + '</h4>' +
                    '<p>' + (flavorLandingEditor.i18n.startFromScratch || 'Empieza desde cero') + '</p>' +
                '</div>' +
            '</div>';

			// Plantillas filtradas
			this.templates.forEach(function (template) {
				if (category !== 'all' && template.category !== category) {
					return;
				}

				html += '<div class="flavor-template-card" data-template-id="' + template.id + '">' +
                    '<div class="flavor-template-preview">';

				if (template.thumbnail) {
					html += '<img src="' + template.thumbnail + '" alt="' + self.escapeHtml(template.name) + '">';
				} else {
					html += '<span class="dashicons dashicons-layout"></span>';
				}

				html += '</div>' +
                    '<div class="flavor-template-info">' +
                        '<h4>' + self.escapeHtml(template.name) + '</h4>' +
                        '<p>' + (template.sections ? template.sections.length : 0) + ' secciones</p>' +
                    '</div>' +
                '</div>';
			});

			$grid.html(html);
		},

		/**
         * Filtra plantillas por categoría
         */
		filterTemplates: function (category) {
			this.renderTemplatesGrid(category);
		},

		/**
         * Cierra el modal de plantillas
         */
		closeTemplatesModal: function () {
			$('#flavor-templates-modal').removeClass('active');
			$('body').removeClass('flavor-modal-open');
		},

		// =========================================
		// TOGGLE PANELES
		// =========================================

		/**
         * Toggle del sidebar (panel de secciones)
         */
		toggleSidebar: function () {
			var $sidebar = $('.flavor-editor-sidebar');
			var $btn = $('#flavor-toggle-sidebar');

			$sidebar.toggleClass('collapsed');
			$btn.toggleClass('active');
		},

		/**
         * Toggle del canvas (editor main)
         */
		toggleCanvas: function () {
			var $canvas = $('.flavor-editor-main');
			var $btn = $('#flavor-toggle-canvas');

			$canvas.toggleClass('collapsed');
			$btn.toggleClass('active');
		},

		/**
         * Carga una plantilla seleccionada
         */
		loadTemplate: function (templateId) {
			var self = this;

			// Plantilla en blanco
			if (templateId === 'blank') {
				if (this.sections.length > 0) {
					if (!confirm(flavorLandingEditor.i18n.confirmClearContent || '¿Estás seguro de que quieres empezar con una página en blanco? Se eliminará todo el contenido actual.')) {
						return;
					}
				}
				this.saveToHistory();
				this.sections = [];
				this.renderCanvas();
				this.refreshPreview();
				this.closeTemplatesModal();
				this.showNotice('success', flavorLandingEditor.i18n.templateLoaded || 'Contenido eliminado');
				this.isDirty = true;
				return;
			}

			// Buscar plantilla
			var template = this.templates.find(function (t) { return t.id === templateId; });
			if (!template) {
				this.showNotice('error', 'Plantilla no encontrada');
				return;
			}

			// Confirmar si hay contenido existente
			if (this.sections.length > 0) {
				if (!confirm(flavorLandingEditor.i18n.confirmReplaceContent || '¿Reemplazar el contenido actual con esta plantilla?')) {
					return;
				}
			}

			// Guardar estado actual antes de cargar
			this.saveToHistory();

			// Cargar secciones de la plantilla (deep copy)
			this.sections = JSON.parse(JSON.stringify(template.sections));

			// Generar nuevos IDs para las secciones
			this.sections.forEach(function (section) {
				section.id = 'section-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
			});

			this.renderCanvas();
			this.refreshPreview();
			this.closeTemplatesModal();
			this.showNotice('success', (flavorLandingEditor.i18n.templateLoaded || 'Plantilla cargada') + ': ' + template.name);
			this.isDirty = true;
		}
	};

	// Inicializar cuando DOM esté listo
	$(document).ready(function () {
		if ($('#flavor-landing-editor').length) {
			FlavorLandingEditor.Editor.init();
		}
	});

})(jQuery);
