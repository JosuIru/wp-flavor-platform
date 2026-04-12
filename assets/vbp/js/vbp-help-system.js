/**
 * VBP Help System
 * Sistema completo de ayuda: atajos, onboarding, ayuda contextual, cambios sin guardar
 */

(function() {
    'use strict';

    // ============================================
    // 1. MODAL DE ATAJOS DE TECLADO
    // ============================================
    window.vbpShortcutsModal = {
        modal: null,
        searchInput: null,
        isOpen: false,
        _initialized: false,
        _eventHandlers: {},

        // Definición de todos los atajos
        shortcuts: {
            general: {
                title: 'General',
                items: [
                    { label: 'Guardar', keys: ['Ctrl', 'S'] },
                    { label: 'Deshacer', keys: ['Ctrl', 'Z'] },
                    { label: 'Rehacer', keys: ['Ctrl', 'Shift', 'Z'] },
                    { label: 'Paleta de comandos', keys: ['Ctrl', '/'] },
                    { label: 'Buscar bloques', keys: ['Ctrl', 'K'] },
                    { label: 'Mostrar atajos', keys: ['?'] },
                    { label: 'Cerrar modal', keys: ['Esc'] }
                ]
            },
            selection: {
                title: 'Selección',
                items: [
                    { label: 'Seleccionar todo', keys: ['Ctrl', 'A'] },
                    { label: 'Deseleccionar', keys: ['Esc'] },
                    { label: 'Selección múltiple', keys: ['Shift', 'Click'] },
                    { label: 'Seleccionar padre', keys: ['↑'] },
                    { label: 'Seleccionar hijo', keys: ['↓'] },
                    { label: 'Seleccionar hermano anterior', keys: ['←'] },
                    { label: 'Seleccionar hermano siguiente', keys: ['→'] }
                ]
            },
            editing: {
                title: 'Edición',
                items: [
                    { label: 'Copiar', keys: ['Ctrl', 'C'] },
                    { label: 'Cortar', keys: ['Ctrl', 'X'] },
                    { label: 'Pegar', keys: ['Ctrl', 'V'] },
                    { label: 'Duplicar', keys: ['Ctrl', 'D'] },
                    { label: 'Eliminar', keys: ['Delete'] },
                    { label: 'Editar texto inline', keys: ['Enter'] },
                    { label: 'Mover arriba', keys: ['Ctrl', '↑'] },
                    { label: 'Mover abajo', keys: ['Ctrl', '↓'] }
                ]
            },
            view: {
                title: 'Vista',
                items: [
                    { label: 'Zoom +', keys: ['Ctrl', '+'] },
                    { label: 'Zoom -', keys: ['Ctrl', '-'] },
                    { label: 'Zoom 100%', keys: ['Ctrl', '0'] },
                    { label: 'Ajustar a pantalla', keys: ['Ctrl', '1'] },
                    { label: 'Vista previa', keys: ['P'] },
                    { label: 'Pantalla completa', keys: ['F11'] },
                    { label: 'Ocultar paneles', keys: ['Tab'] }
                ]
            },
            panels: {
                title: 'Paneles',
                items: [
                    { label: 'Panel bloques', keys: ['B'] },
                    { label: 'Panel capas', keys: ['L'] },
                    { label: 'Panel inspector', keys: ['I'] },
                    { label: 'Panel historial', keys: ['H'] },
                    { label: 'Panel AI', keys: ['A'] },
                    { label: 'Minimap', keys: ['M'] }
                ]
            },
            responsive: {
                title: 'Responsive',
                items: [
                    { label: 'Vista Desktop', keys: ['D'] },
                    { label: 'Vista Tablet', keys: ['T'] },
                    { label: 'Vista Mobile', keys: ['M'] },
                    { label: 'Rotar dispositivo', keys: ['R'] }
                ]
            }
        },

        init: function() {
            if (this._initialized) return;
            this._initialized = true;

            this.createModal();
            this.bindEvents();
        },

        destroy: function() {
            // Remover event listeners
            if (this._eventHandlers.closeClick && this.modal) {
                var closeBtn = this.modal.querySelector('.vbp-shortcuts-close');
                if (closeBtn) closeBtn.removeEventListener('click', this._eventHandlers.closeClick);
            }
            if (this._eventHandlers.modalClick && this.modal) {
                this.modal.removeEventListener('click', this._eventHandlers.modalClick);
            }
            if (this._eventHandlers.modalKeydown && this.modal) {
                this.modal.removeEventListener('keydown', this._eventHandlers.modalKeydown);
            }
            if (this._eventHandlers.searchInput && this.searchInput) {
                this.searchInput.removeEventListener('input', this._eventHandlers.searchInput);
            }
            if (this._eventHandlers.globalKeydown) {
                document.removeEventListener('keydown', this._eventHandlers.globalKeydown);
            }

            // Remover elementos del DOM
            if (this.modal && this.modal.parentNode) {
                this.modal.parentNode.removeChild(this.modal);
            }

            // Resetear estado
            this.modal = null;
            this.searchInput = null;
            this.isOpen = false;
            this._eventHandlers = {};
            this._initialized = false;
        },

        createModal: function() {
            var html = '<div class="vbp-shortcuts-modal" role="dialog" aria-modal="true" aria-labelledby="vbp-shortcuts-title">' +
                '<div class="vbp-shortcuts-content">' +
                    '<div class="vbp-shortcuts-header">' +
                        '<h2 class="vbp-shortcuts-title" id="vbp-shortcuts-title">' +
                            '<span class="material-icons">keyboard</span>' +
                            'Atajos de teclado' +
                        '</h2>' +
                        '<button class="vbp-shortcuts-close" type="button" aria-label="Cerrar">' +
                            '<span class="material-icons">close</span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="vbp-shortcuts-search">' +
                        '<input type="text" class="vbp-shortcuts-search-input" placeholder="Buscar atajo...">' +
                    '</div>' +
                    '<div class="vbp-shortcuts-body">' +
                        this.renderCategories() +
                    '</div>' +
                    '<div class="vbp-shortcuts-footer">' +
                        '<span class="vbp-shortcuts-hint">' +
                            '<span class="vbp-key">?</span> para abrir/cerrar' +
                        '</span>' +
                        '<span>Pulsa cualquier atajo para usarlo</span>' +
                    '</div>' +
                '</div>' +
            '</div>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            this.modal = tempContainer.firstChild;
            document.body.appendChild(this.modal);
            this.searchInput = this.modal.querySelector('.vbp-shortcuts-search-input');
        },

        renderCategories: function() {
            var self = this;
            var html = '';

            Object.keys(this.shortcuts).forEach(function(categoryKey) {
                var category = self.shortcuts[categoryKey];
                html += '<div class="vbp-shortcuts-category" data-category="' + categoryKey + '">' +
                    '<div class="vbp-shortcuts-category-title">' + category.title + '</div>' +
                    '<div class="vbp-shortcuts-grid">';

                category.items.forEach(function(shortcut) {
                    html += '<div class="vbp-shortcut-item" data-search="' + shortcut.label.toLowerCase() + '">' +
                        '<span class="vbp-shortcut-label">' + shortcut.label + '</span>' +
                        '<span class="vbp-shortcut-keys">' +
                            shortcut.keys.map(function(key) {
                                var cssClass = key.length > 1 ? 'vbp-key vbp-key--wide' : 'vbp-key';
                                return '<span class="' + cssClass + '">' + key + '</span>';
                            }).join('') +
                        '</span>' +
                    '</div>';
                });

                html += '</div></div>';
            });

            return html;
        },

        bindEvents: function() {
            var self = this;

            // Cerrar con botón
            this._eventHandlers.closeClick = function() {
                self.close();
            };
            this.modal.querySelector('.vbp-shortcuts-close').addEventListener('click', this._eventHandlers.closeClick);

            // Cerrar con click fuera
            this._eventHandlers.modalClick = function(e) {
                if (e.target === self.modal) {
                    self.close();
                }
            };
            this.modal.addEventListener('click', this._eventHandlers.modalClick);

            // Cerrar con Escape
            this._eventHandlers.modalKeydown = function(e) {
                if (e.key === 'Escape') {
                    self.close();
                }
            };
            this.modal.addEventListener('keydown', this._eventHandlers.modalKeydown);

            // Búsqueda
            this._eventHandlers.searchInput = function() {
                self.filterShortcuts(self.searchInput.value);
            };
            this.searchInput.addEventListener('input', this._eventHandlers.searchInput);

            // Atajo global para abrir (?)
            this._eventHandlers.globalKeydown = function(e) {
                if (e.key === '?' && !self.isInputFocused()) {
                    e.preventDefault();
                    self.toggle();
                }
            };
            document.addEventListener('keydown', this._eventHandlers.globalKeydown);
        },

        filterShortcuts: function(query) {
            var normalizedQuery = query.toLowerCase().trim();
            var items = this.modal.querySelectorAll('.vbp-shortcut-item');
            var categories = this.modal.querySelectorAll('.vbp-shortcuts-category');

            items.forEach(function(item) {
                var searchText = item.dataset.search || '';
                var visible = !normalizedQuery || searchText.includes(normalizedQuery);
                item.classList.toggle('is-hidden', !visible);
            });

            // Ocultar categorías vacías
            categories.forEach(function(category) {
                var visibleItems = category.querySelectorAll('.vbp-shortcut-item:not(.is-hidden)');
                category.style.display = visibleItems.length > 0 ? '' : 'none';
            });
        },

        isInputFocused: function() {
            var activeEl = document.activeElement;
            return activeEl && (
                activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                activeEl.isContentEditable
            );
        },

        open: function() {
            this.modal.classList.add('is-open');
            this.isOpen = true;
            this.searchInput.value = '';
            this.filterShortcuts('');
            this.searchInput.focus();
        },

        close: function() {
            this.modal.classList.remove('is-open');
            this.isOpen = false;
        },

        toggle: function() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }
    };

    // ============================================
    // 2. ONBOARDING / TOUR GUIADO
    // ============================================
    window.vbpOnboarding = {
        overlay: null,
        highlight: null,
        tooltip: null,
        currentStep: 0,
        isActive: false,
        hasCompletedOnce: false,

        // Definición de pasos del tour
        steps: [
            {
                target: '.vbp-toolbar',
                title: 'Barra de herramientas',
                description: 'Aquí encontrarás las acciones principales: guardar, deshacer, zoom y opciones de vista.',
                tip: 'Usa Ctrl+S para guardar rápidamente',
                icon: 'construction',
                position: 'bottom'
            },
            {
                target: '.vbp-panel-blocks, .vbp-blocks-panel',
                title: 'Panel de bloques',
                description: 'Arrastra bloques desde aquí al canvas para construir tu página. Están organizados por categorías.',
                tip: 'Pulsa B para abrir/cerrar este panel',
                icon: 'widgets',
                position: 'right'
            },
            {
                target: '.vbp-canvas-container, .vbp-canvas',
                title: 'Canvas de diseño',
                description: 'Este es tu lienzo de trabajo. Arrastra, redimensiona y organiza los elementos aquí.',
                tip: 'Usa la rueda del ratón + Ctrl para hacer zoom',
                icon: 'brush',
                position: 'left'
            },
            {
                target: '.vbp-panel-inspector, .vbp-inspector-panel',
                title: 'Inspector de propiedades',
                description: 'Personaliza el elemento seleccionado: colores, tipografía, espaciado y más.',
                tip: 'Pulsa I para abrir/cerrar el inspector',
                icon: 'tune',
                position: 'left'
            },
            {
                target: '.vbp-panel-layers, .vbp-layers-panel',
                title: 'Panel de capas',
                description: 'Visualiza la estructura de tu página en forma de árbol. Reordena elementos fácilmente.',
                tip: 'Pulsa L para abrir/cerrar las capas',
                icon: 'layers',
                position: 'right'
            },
            {
                target: '.vbp-responsive-controls, .vbp-breakpoint-selector',
                title: 'Controles responsive',
                description: 'Visualiza y edita tu diseño en diferentes tamaños: desktop, tablet y móvil.',
                tip: 'Pulsa D, T o M para cambiar de vista',
                icon: 'devices',
                position: 'bottom'
            },
            {
                target: '.vbp-ai-button, .vbp-ai-assistant-btn',
                title: 'Asistente IA',
                description: 'Genera contenido, sugiere diseños y optimiza tu página con ayuda de la inteligencia artificial.',
                tip: 'Pulsa A para abrir el asistente',
                icon: 'auto_awesome',
                position: 'left'
            }
        ],

        init: function() {
            this.checkFirstVisit();
        },

        checkFirstVisit: function() {
            var hasVisited = localStorage.getItem('vbp_onboarding_completed');
            if (!hasVisited) {
                // Esperar a que el editor esté listo
                var self = this;
                setTimeout(function() {
                    self.showWelcome();
                }, 1000);
            }
        },

        showWelcome: function() {
            var self = this;
            var html = '<div class="vbp-onboarding-welcome">' +
                '<div class="vbp-onboarding-welcome-content vbp-animate-bounce-in">' +
                    '<div class="vbp-onboarding-welcome-icon">🎨</div>' +
                    '<h2 class="vbp-onboarding-welcome-title">¡Bienvenido a Visual Builder Pro!</h2>' +
                    '<p class="vbp-onboarding-welcome-subtitle">' +
                        'Crea páginas increíbles de forma visual. ¿Te gustaría un tour rápido para conocer las herramientas?' +
                    '</p>' +
                    '<div class="vbp-onboarding-welcome-actions">' +
                        '<button class="vbp-onboarding-welcome-btn vbp-onboarding-welcome-btn--start" type="button">' +
                            'Sí, muéstrame' +
                        '</button>' +
                        '<button class="vbp-onboarding-welcome-btn vbp-onboarding-welcome-btn--skip" type="button">' +
                            'Saltar tour' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            var welcomeScreen = tempContainer.firstChild;
            document.body.appendChild(welcomeScreen);

            welcomeScreen.querySelector('.vbp-onboarding-welcome-btn--start').addEventListener('click', function() {
                welcomeScreen.remove();
                self.start();
            });

            welcomeScreen.querySelector('.vbp-onboarding-welcome-btn--skip').addEventListener('click', function() {
                welcomeScreen.remove();
                self.markCompleted();
            });
        },

        createElements: function() {
            // Overlay
            this.overlay = document.createElement('div');
            this.overlay.className = 'vbp-onboarding-overlay';
            document.body.appendChild(this.overlay);

            // Highlight
            this.highlight = document.createElement('div');
            this.highlight.className = 'vbp-onboarding-highlight';
            document.body.appendChild(this.highlight);

            // Tooltip
            this.tooltip = document.createElement('div');
            this.tooltip.className = 'vbp-onboarding-tooltip';
            document.body.appendChild(this.tooltip);
        },

        start: function() {
            this.createElements();
            this.currentStep = 0;
            this.isActive = true;
            this.showStep(0);
        },

        showStep: function(stepIndex) {
            var self = this;
            var step = this.steps[stepIndex];
            if (!step) {
                this.complete();
                return;
            }

            var targetEl = document.querySelector(step.target);
            if (!targetEl) {
                // Saltar al siguiente paso si no encuentra el elemento
                this.currentStep++;
                this.showStep(this.currentStep);
                return;
            }

            // Activar overlay
            this.overlay.classList.add('is-active');

            // Posicionar highlight
            var rect = targetEl.getBoundingClientRect();
            this.highlight.style.top = (rect.top - 8) + 'px';
            this.highlight.style.left = (rect.left - 8) + 'px';
            this.highlight.style.width = (rect.width + 16) + 'px';
            this.highlight.style.height = (rect.height + 16) + 'px';

            // Renderizar tooltip
            this.tooltip.innerHTML = this.renderTooltip(step, stepIndex);
            this.tooltip.setAttribute('data-position', step.position);

            // Posicionar tooltip
            setTimeout(function() {
                self.positionTooltip(rect, step.position);
                self.tooltip.classList.add('is-visible');
            }, 100);

            // Bind eventos del tooltip
            this.bindTooltipEvents();
        },

        renderTooltip: function(step, stepIndex) {
            var totalSteps = this.steps.length;
            var html = '<div class="vbp-onboarding-header">' +
                '<div class="vbp-onboarding-icon">' +
                    '<span class="material-icons">' + step.icon + '</span>' +
                '</div>' +
                '<div class="vbp-onboarding-step-info">' +
                    '<div class="vbp-onboarding-step-count">Paso ' + (stepIndex + 1) + ' de ' + totalSteps + '</div>' +
                    '<h3 class="vbp-onboarding-step-title">' + step.title + '</h3>' +
                '</div>' +
            '</div>' +
            '<div class="vbp-onboarding-body">' +
                '<p class="vbp-onboarding-description">' + step.description + '</p>';

            if (step.tip) {
                html += '<div class="vbp-onboarding-tip">' +
                    '<span class="material-icons">lightbulb</span>' +
                    '<span>' + step.tip + '</span>' +
                '</div>';
            }

            html += '</div>' +
            '<div class="vbp-onboarding-footer">' +
                '<div class="vbp-onboarding-progress">';

            for (var i = 0; i < totalSteps; i++) {
                var dotClass = 'vbp-onboarding-dot';
                if (i < stepIndex) dotClass += ' is-completed';
                if (i === stepIndex) dotClass += ' is-active';
                html += '<div class="' + dotClass + '"></div>';
            }

            html += '</div>' +
                '<div class="vbp-onboarding-actions">' +
                    '<button class="vbp-onboarding-btn vbp-onboarding-btn--skip" type="button">Saltar</button>';

            if (stepIndex > 0) {
                html += '<button class="vbp-onboarding-btn vbp-onboarding-btn--prev" type="button">Anterior</button>';
            }

            var nextLabel = stepIndex === totalSteps - 1 ? 'Finalizar' : 'Siguiente';
            html += '<button class="vbp-onboarding-btn vbp-onboarding-btn--next" type="button">' + nextLabel + '</button>' +
                '</div>' +
            '</div>';

            return html;
        },

        positionTooltip: function(targetRect, position) {
            var tooltipRect = this.tooltip.getBoundingClientRect();
            var margin = 20;
            var top, left;

            switch (position) {
                case 'top':
                    top = targetRect.top - tooltipRect.height - margin;
                    left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                    break;
                case 'bottom':
                    top = targetRect.bottom + margin;
                    left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                    break;
                case 'left':
                    top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                    left = targetRect.left - tooltipRect.width - margin;
                    break;
                case 'right':
                    top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                    left = targetRect.right + margin;
                    break;
            }

            // Mantener dentro de la pantalla
            left = Math.max(10, Math.min(left, window.innerWidth - tooltipRect.width - 10));
            top = Math.max(10, Math.min(top, window.innerHeight - tooltipRect.height - 10));

            this.tooltip.style.top = top + 'px';
            this.tooltip.style.left = left + 'px';
        },

        bindTooltipEvents: function() {
            var self = this;

            var skipBtn = this.tooltip.querySelector('.vbp-onboarding-btn--skip');
            var prevBtn = this.tooltip.querySelector('.vbp-onboarding-btn--prev');
            var nextBtn = this.tooltip.querySelector('.vbp-onboarding-btn--next');

            if (skipBtn) {
                skipBtn.addEventListener('click', function() {
                    self.complete();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    self.currentStep--;
                    self.tooltip.classList.remove('is-visible');
                    setTimeout(function() {
                        self.showStep(self.currentStep);
                    }, 200);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    self.currentStep++;
                    self.tooltip.classList.remove('is-visible');
                    setTimeout(function() {
                        self.showStep(self.currentStep);
                    }, 200);
                });
            }
        },

        complete: function() {
            this.isActive = false;
            this.markCompleted();

            // Limpiar elementos
            if (this.overlay) this.overlay.remove();
            if (this.highlight) this.highlight.remove();
            if (this.tooltip) this.tooltip.remove();

            // Mostrar mensaje de completado
            if (window.vbpToast) {
                vbpToast.success('¡Tour completado! Ya puedes empezar a crear.');
            }
        },

        markCompleted: function() {
            localStorage.setItem('vbp_onboarding_completed', '1');
            this.hasCompletedOnce = true;
        },

        restart: function() {
            this.start();
        }
    };

    // ============================================
    // 3. PANEL DE AYUDA CONTEXTUAL
    // ============================================
    window.vbpContextualHelp = {
        panel: null,
        toggleBtn: null,
        isVisible: false,
        currentContext: null,

        // Definición de ayuda por contexto
        contextHelp: {
            'default': {
                title: 'Ayuda rápida',
                description: 'Selecciona un elemento para ver ayuda específica.',
                shortcuts: [
                    { label: 'Mostrar atajos', keys: ['?'] },
                    { label: 'Paleta comandos', keys: ['Ctrl', '/'] },
                    { label: 'Guardar', keys: ['Ctrl', 'S'] }
                ]
            },
            'section': {
                title: 'Sección',
                description: 'Las secciones son contenedores de ancho completo. Úsalas para organizar el contenido de tu página.',
                shortcuts: [
                    { label: 'Duplicar', keys: ['Ctrl', 'D'] },
                    { label: 'Eliminar', keys: ['Delete'] },
                    { label: 'Mover', keys: ['Ctrl', '↑↓'] }
                ],
                tip: 'Añade un Container dentro para centrar el contenido.'
            },
            'container': {
                title: 'Contenedor',
                description: 'Los contenedores limitan el ancho del contenido y lo centran en la página.',
                shortcuts: [
                    { label: 'Duplicar', keys: ['Ctrl', 'D'] },
                    { label: 'Editar padding', keys: ['Shift', 'Click'] }
                ]
            },
            'columns': {
                title: 'Columnas',
                description: 'Divide el espacio horizontal en columnas. Puedes ajustar el ancho arrastrando los separadores.',
                shortcuts: [
                    { label: 'Añadir columna', keys: ['+'] },
                    { label: 'Igualar anchos', keys: ['='] }
                ],
                tip: 'En móvil las columnas se apilan automáticamente.'
            },
            'heading': {
                title: 'Título',
                description: 'Usa títulos para estructurar tu contenido. H1 para el principal, H2-H6 para subsecciones.',
                shortcuts: [
                    { label: 'Editar inline', keys: ['Enter'] },
                    { label: 'Cambiar nivel', keys: ['Ctrl', '1-6'] }
                ]
            },
            'text': {
                title: 'Texto',
                description: 'Bloque de párrafo para contenido textual. Soporta formato enriquecido.',
                shortcuts: [
                    { label: 'Editar inline', keys: ['Enter'] },
                    { label: 'Negrita', keys: ['Ctrl', 'B'] },
                    { label: 'Cursiva', keys: ['Ctrl', 'I'] },
                    { label: 'Enlace', keys: ['Ctrl', 'K'] }
                ]
            },
            'button': {
                title: 'Botón',
                description: 'Botones de acción con múltiples estilos: primario, secundario, outline y ghost.',
                shortcuts: [
                    { label: 'Editar texto', keys: ['Enter'] },
                    { label: 'Cambiar estilo', keys: ['Tab'] }
                ]
            },
            'image': {
                title: 'Imagen',
                description: 'Añade imágenes desde la biblioteca de medios o por URL externa.',
                shortcuts: [
                    { label: 'Abrir biblioteca', keys: ['Enter'] },
                    { label: 'Ajustar tamaño', keys: ['Shift', 'Drag'] }
                ],
                tip: 'Añade siempre texto alternativo para accesibilidad.'
            },
            'video': {
                title: 'Video',
                description: 'Incrusta videos de YouTube, Vimeo o sube tus propios archivos.',
                shortcuts: [],
                tip: 'Los videos de YouTube se optimizan automáticamente.'
            },
            'form': {
                title: 'Formulario',
                description: 'Formularios de contacto, suscripción o personalizados.',
                shortcuts: [
                    { label: 'Añadir campo', keys: ['+'] }
                ],
                tip: 'Configura la acción del formulario en el inspector.'
            },
            'carousel': {
                title: 'Carrusel',
                description: 'Muestra múltiples elementos en un slider con navegación.',
                shortcuts: [],
                tip: 'Añade al menos 3 slides para mejor experiencia.'
            },
            'tabs': {
                title: 'Pestañas',
                description: 'Organiza contenido en pestañas para ahorrar espacio.',
                shortcuts: [
                    { label: 'Añadir pestaña', keys: ['+'] },
                    { label: 'Navegar', keys: ['←', '→'] }
                ]
            },
            'accordion': {
                title: 'Acordeón',
                description: 'Secciones colapsables ideales para FAQs o contenido largo.',
                shortcuts: [
                    { label: 'Añadir item', keys: ['+'] }
                ]
            },
            'module': {
                title: 'Módulo dinámico',
                description: 'Muestra contenido de módulos de Flavor: eventos, productos, socios, etc.',
                shortcuts: [],
                tip: 'Configura las opciones de visualización en el inspector.'
            }
        },

        init: function() {
            this.createPanel();
            this.createToggleButton();
            this.bindEvents();
        },

        createPanel: function() {
            var html = '<div class="vbp-contextual-help">' +
                '<div class="vbp-contextual-help-header">' +
                    '<span class="vbp-contextual-help-title">' +
                        '<span class="material-icons">help_outline</span>' +
                        '<span class="vbp-contextual-help-title-text">Ayuda</span>' +
                    '</span>' +
                    '<button class="vbp-contextual-help-close" type="button" aria-label="Cerrar">' +
                        '<span class="material-icons">close</span>' +
                    '</button>' +
                '</div>' +
                '<div class="vbp-contextual-help-body">' +
                    this.renderContext('default') +
                '</div>' +
            '</div>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            this.panel = tempContainer.firstChild;
            document.body.appendChild(this.panel);
        },

        createToggleButton: function() {
            var html = '<button class="vbp-help-toggle" type="button" aria-label="Ayuda">' +
                '<span class="material-icons">help_outline</span>' +
            '</button>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            this.toggleBtn = tempContainer.firstChild;
            document.body.appendChild(this.toggleBtn);
        },

        renderContext: function(contextKey) {
            var context = this.contextHelp[contextKey] || this.contextHelp['default'];
            var html = '<div class="vbp-contextual-help-section">' +
                '<div class="vbp-contextual-help-label">' + context.title + '</div>' +
                '<p class="vbp-contextual-help-text">' + context.description + '</p>' +
            '</div>';

            if (context.shortcuts && context.shortcuts.length > 0) {
                html += '<div class="vbp-contextual-help-section">' +
                    '<div class="vbp-contextual-help-label">Atajos</div>' +
                    '<div class="vbp-contextual-help-shortcuts">';

                context.shortcuts.forEach(function(shortcut) {
                    html += '<div class="vbp-contextual-shortcut">' +
                        '<span class="vbp-contextual-shortcut-label">' + shortcut.label + '</span>' +
                        '<span class="vbp-contextual-shortcut-keys">' +
                            shortcut.keys.map(function(key) {
                                return '<span class="vbp-key">' + key + '</span>';
                            }).join('') +
                        '</span>' +
                    '</div>';
                });

                html += '</div></div>';
            }

            if (context.tip) {
                html += '<div class="vbp-contextual-help-section">' +
                    '<div class="vbp-contextual-help-tip">' +
                        '<span class="material-icons">tips_and_updates</span>' +
                        '<span>' + context.tip + '</span>' +
                    '</div>' +
                '</div>';
            }

            return html;
        },

        bindEvents: function() {
            var self = this;

            this.toggleBtn.addEventListener('click', function() {
                self.toggle();
            });

            this.panel.querySelector('.vbp-contextual-help-close').addEventListener('click', function() {
                self.hide();
            });

            // Escuchar cambios de selección
            document.addEventListener('vbp:selection-change', function(e) {
                if (e.detail && e.detail.type) {
                    self.updateContext(e.detail.type);
                }
            });

            // Detectar selección por click en canvas
            var canvasEl = document.querySelector('.vbp-canvas, .vbp-canvas-container');
            if (canvasEl) {
                canvasEl.addEventListener('click', function(e) {
                    var blockEl = e.target.closest('[data-block-type]');
                    if (blockEl) {
                        self.updateContext(blockEl.dataset.blockType);
                    }
                });
            }
        },

        updateContext: function(contextKey) {
            if (this.currentContext === contextKey) return;
            this.currentContext = contextKey;

            var body = this.panel.querySelector('.vbp-contextual-help-body');
            body.innerHTML = this.renderContext(contextKey);

            // Actualizar título
            var context = this.contextHelp[contextKey] || this.contextHelp['default'];
            this.panel.querySelector('.vbp-contextual-help-title-text').textContent = context.title;
        },

        show: function() {
            this.panel.classList.add('is-visible');
            this.toggleBtn.classList.add('is-active');
            this.isVisible = true;
        },

        hide: function() {
            this.panel.classList.remove('is-visible');
            this.toggleBtn.classList.remove('is-active');
            this.isVisible = false;
        },

        toggle: function() {
            if (this.isVisible) {
                this.hide();
            } else {
                this.show();
            }
        }
    };

    // ============================================
    // 4. INDICADOR DE CAMBIOS SIN GUARDAR
    // ============================================
    window.vbpUnsavedChanges = {
        indicator: null,
        leaveConfirm: null,
        hasChanges: false,
        lastChangeTime: null,
        changeCount: 0,
        autoSaveInterval: null,
        _initialized: false,
        _eventHandlers: {},

        init: function() {
            if (this._initialized) return;
            this._initialized = true;

            this.createIndicator();
            this.createLeaveConfirm();
            this.bindEvents();
            this.startAutoSaveTimer();
        },

        destroy: function() {
            // Limpiar interval
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
                this.autoSaveInterval = null;
            }

            // Remover event listeners
            if (this._eventHandlers.contentChange) {
                document.removeEventListener('vbp:content-change', this._eventHandlers.contentChange);
            }
            if (this._eventHandlers.saved) {
                document.removeEventListener('vbp:saved', this._eventHandlers.saved);
            }
            if (this._eventHandlers.beforeUnload) {
                window.removeEventListener('beforeunload', this._eventHandlers.beforeUnload);
            }
            if (this._eventHandlers.keydown) {
                document.removeEventListener('keydown', this._eventHandlers.keydown);
            }

            // Remover elementos del DOM
            if (this.indicator && this.indicator.parentNode) {
                this.indicator.parentNode.removeChild(this.indicator);
            }
            if (this.leaveConfirm && this.leaveConfirm.parentNode) {
                this.leaveConfirm.parentNode.removeChild(this.leaveConfirm);
            }

            // Resetear estado
            this.indicator = null;
            this.leaveConfirm = null;
            this.hasChanges = false;
            this._eventHandlers = {};
            this._initialized = false;
        },

        createIndicator: function() {
            var html = '<div class="vbp-unsaved-indicator">' +
                '<div class="vbp-unsaved-dot"></div>' +
                '<span class="vbp-unsaved-text">Cambios sin guardar</span>' +
                '<span class="vbp-unsaved-time"></span>' +
                '<div class="vbp-unsaved-actions">' +
                    '<button class="vbp-unsaved-btn vbp-unsaved-btn--save" type="button">Guardar</button>' +
                    '<button class="vbp-unsaved-btn vbp-unsaved-btn--discard" type="button">Descartar</button>' +
                '</div>' +
            '</div>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            this.indicator = tempContainer.firstChild;
            document.body.appendChild(this.indicator);
        },

        createLeaveConfirm: function() {
            var html = '<div class="vbp-leave-confirm">' +
                '<div class="vbp-leave-confirm-content">' +
                    '<div class="vbp-leave-confirm-icon">' +
                        '<span class="material-icons">warning</span>' +
                    '</div>' +
                    '<h3 class="vbp-leave-confirm-title">¿Guardar cambios?</h3>' +
                    '<p class="vbp-leave-confirm-message">' +
                        'Tienes cambios sin guardar. ¿Qué quieres hacer?' +
                    '</p>' +
                    '<div class="vbp-leave-confirm-actions">' +
                        '<button class="vbp-leave-confirm-btn vbp-leave-confirm-btn--save" type="button">Guardar</button>' +
                        '<button class="vbp-leave-confirm-btn vbp-leave-confirm-btn--discard" type="button">Descartar</button>' +
                        '<button class="vbp-leave-confirm-btn vbp-leave-confirm-btn--cancel" type="button">Cancelar</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            var tempContainer = document.createElement('div');
            tempContainer.innerHTML = html;
            this.leaveConfirm = tempContainer.firstChild;
            document.body.appendChild(this.leaveConfirm);
        },

        bindEvents: function() {
            var self = this;

            // Botones del indicador
            this.indicator.querySelector('.vbp-unsaved-btn--save').addEventListener('click', function() {
                self.save();
            });

            this.indicator.querySelector('.vbp-unsaved-btn--discard').addEventListener('click', function() {
                self.showLeaveConfirm('discard');
            });

            // Botones del confirm
            this.leaveConfirm.querySelector('.vbp-leave-confirm-btn--save').addEventListener('click', function() {
                self.save();
                self.hideLeaveConfirm();
            });

            this.leaveConfirm.querySelector('.vbp-leave-confirm-btn--discard').addEventListener('click', function() {
                self.discard();
                self.hideLeaveConfirm();
            });

            this.leaveConfirm.querySelector('.vbp-leave-confirm-btn--cancel').addEventListener('click', function() {
                self.hideLeaveConfirm();
            });

            // Detectar cambios (guardar referencia para cleanup)
            this._eventHandlers.contentChange = function() {
                self.markChanged();
            };
            document.addEventListener('vbp:content-change', this._eventHandlers.contentChange);

            this._eventHandlers.saved = function() {
                self.markSaved();
            };
            document.addEventListener('vbp:saved', this._eventHandlers.saved);

            // Prevenir salir sin guardar
            this._eventHandlers.beforeUnload = function(e) {
                if (self.hasChanges) {
                    e.preventDefault();
                    e.returnValue = 'Tienes cambios sin guardar. ¿Seguro que quieres salir?';
                    return e.returnValue;
                }
            };
            window.addEventListener('beforeunload', this._eventHandlers.beforeUnload);

            // Detectar cambios en Alpine store si existe
            document.addEventListener('alpine:init', function() {
                if (window.Alpine && Alpine.store) {
                    var originalSetContent = Alpine.store('vbp')?.setContent;
                    if (originalSetContent) {
                        Alpine.store('vbp').setContent = function() {
                            originalSetContent.apply(this, arguments);
                            self.markChanged();
                        };
                    }
                }
            });

            // Escuchar Ctrl+S
            this._eventHandlers.keydown = function(e) {
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    self.save();
                }
            };
            document.addEventListener('keydown', this._eventHandlers.keydown);
        },

        markChanged: function() {
            this.hasChanges = true;
            this.lastChangeTime = new Date();
            this.changeCount++;
            this.updateIndicator();
            this.showIndicator();
        },

        markSaved: function() {
            this.hasChanges = false;
            this.changeCount = 0;
            this.hideIndicator();

            if (window.vbpToast) {
                vbpToast.success('Guardado correctamente');
            }
        },

        updateIndicator: function() {
            var timeEl = this.indicator.querySelector('.vbp-unsaved-time');
            if (this.lastChangeTime) {
                var diff = Math.round((new Date() - this.lastChangeTime) / 1000);
                if (diff < 60) {
                    timeEl.textContent = 'hace ' + diff + 's';
                } else {
                    timeEl.textContent = 'hace ' + Math.round(diff / 60) + 'm';
                }
            }
        },

        showIndicator: function() {
            this.indicator.classList.add('is-visible');
        },

        hideIndicator: function() {
            this.indicator.classList.remove('is-visible');
        },

        showLeaveConfirm: function(action) {
            this.pendingAction = action;
            this.leaveConfirm.classList.add('is-open');
        },

        hideLeaveConfirm: function() {
            this.leaveConfirm.classList.remove('is-open');
            this.pendingAction = null;
        },

        save: function() {
            // Disparar evento de guardar
            var saveEvent = new CustomEvent('vbp:save-request');
            document.dispatchEvent(saveEvent);

            // También intentar click en botón de guardar
            var saveBtn = document.querySelector('.vbp-save-btn, [data-action="save"]');
            if (saveBtn) {
                saveBtn.click();
            }
        },

        discard: function() {
            // Disparar evento de descartar
            var discardEvent = new CustomEvent('vbp:discard-request');
            document.dispatchEvent(discardEvent);

            this.hasChanges = false;
            this.changeCount = 0;
            this.hideIndicator();

            // Recargar página para descartar cambios
            if (confirm('¿Recargar la página para descartar todos los cambios?')) {
                window.location.reload();
            }
        },

        startAutoSaveTimer: function() {
            var self = this;
            // Actualizar el tiempo cada 10 segundos
            this.autoSaveInterval = setInterval(function() {
                if (self.hasChanges) {
                    self.updateIndicator();
                }
            }, 10000);
        }
    };

    // ============================================
    // INICIALIZACIÓN
    // ============================================
    var helpSystemInitialized = false;

    function initHelpSystem() {
        // Evitar doble inicialización
        if (helpSystemInitialized) return;

        // Solo inicializar si estamos en el editor VBP
        if (!document.querySelector('.vbp-editor')) {
            return;
        }

        helpSystemInitialized = true;

        vbpShortcutsModal.init();
        vbpOnboarding.init();
        vbpContextualHelp.init();
        vbpUnsavedChanges.init();

        console.log('[VBP] Help System initialized');
    }

    // Función para destruir todo el sistema de ayuda
    window.vbpHelpSystemDestroy = function() {
        if (vbpShortcutsModal.destroy) vbpShortcutsModal.destroy();
        if (vbpUnsavedChanges.destroy) vbpUnsavedChanges.destroy();
        helpSystemInitialized = false;
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHelpSystem);
    } else {
        initHelpSystem();
    }

    // También inicializar cuando Alpine esté listo (por si acaso)
    document.addEventListener('alpine:initialized', function() {
        setTimeout(initHelpSystem, 500);
    });

})();
