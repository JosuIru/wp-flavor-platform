/**
 * Visual Builder Pro - Code Editor
 *
 * Editor de codigo integrado con Monaco Editor para
 * crear y editar Code Components.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no esta definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Configuracion de Monaco Editor
     */
    const MONACO_CONFIG = {
        cdn: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs',
        defaultTheme: 'vs-dark',
        themes: {
            'vs-dark': {
                name: 'Dark',
                base: 'vs-dark'
            },
            'vs-light': {
                name: 'Light',
                base: 'vs'
            },
            'hc-black': {
                name: 'High Contrast',
                base: 'hc-black'
            },
            'vbp-dark': {
                name: 'VBP Dark',
                base: 'vs-dark',
                rules: [
                    { token: 'comment', foreground: '6A9955' },
                    { token: 'keyword', foreground: 'C586C0' },
                    { token: 'string', foreground: 'CE9178' },
                    { token: 'number', foreground: 'B5CEA8' },
                    { token: 'type', foreground: '4EC9B0' },
                    { token: 'function', foreground: 'DCDCAA' },
                    { token: 'variable', foreground: '9CDCFE' },
                    { token: 'tag', foreground: '569CD6' },
                    { token: 'attribute.name', foreground: '9CDCFE' },
                    { token: 'attribute.value', foreground: 'CE9178' }
                ],
                colors: {
                    'editor.background': '#1e1e2e',
                    'editor.foreground': '#cdd6f4',
                    'editor.lineHighlightBackground': '#313244',
                    'editor.selectionBackground': '#45475a',
                    'editorCursor.foreground': '#f5e0dc',
                    'editorLineNumber.foreground': '#6c7086',
                    'editorLineNumber.activeForeground': '#cdd6f4'
                }
            }
        }
    };

    /**
     * Mapeo de lenguajes por framework
     */
    const LANGUAGE_MAP = {
        'react': 'javascript',
        'vue': 'html',
        'svelte': 'html',
        'vanilla': 'javascript',
        'typescript': 'typescript',
        'jsx': 'javascript',
        'tsx': 'typescript'
    };

    /**
     * Snippets predefinidos por framework
     */
    const CODE_SNIPPETS = {
        'react': {
            'useState': {
                label: 'useState Hook',
                insertText: 'const [${1:state}, set${1/(.*)/${1:/capitalize}/}] = React.useState(${2:initialValue});',
                documentation: 'React useState hook'
            },
            'useEffect': {
                label: 'useEffect Hook',
                insertText: 'React.useEffect(() => {\n\t${1:// effect}\n\treturn () => {\n\t\t${2:// cleanup}\n\t};\n}, [${3:dependencies}]);',
                documentation: 'React useEffect hook'
            },
            'component': {
                label: 'Functional Component',
                insertText: 'function ${1:Component}({ ${2:props} }) {\n\treturn (\n\t\t<div>\n\t\t\t${3:content}\n\t\t</div>\n\t);\n}',
                documentation: 'React functional component'
            },
            'handler': {
                label: 'Event Handler',
                insertText: 'const handle${1:Event} = (event) => {\n\t${2:// handle event}\n};',
                documentation: 'Event handler function'
            }
        },
        'vue': {
            'template': {
                label: 'Vue Template',
                insertText: '<template>\n\t<div>\n\t\t${1:content}\n\t</div>\n</template>',
                documentation: 'Vue template block'
            },
            'script': {
                label: 'Vue Script',
                insertText: '<script>\nexport default {\n\tprops: {\n\t\t${1:propName}: {\n\t\t\ttype: ${2:String},\n\t\t\tdefault: ${3:\'\'}\n\t\t}\n\t},\n\tdata() {\n\t\treturn {\n\t\t\t${4:state}: ${5:null}\n\t\t};\n\t}\n};\n</script>',
                documentation: 'Vue script block'
            },
            'setup': {
                label: 'Vue Setup',
                insertText: '<script setup>\nimport { ref, computed } from \'vue\';\n\nconst ${1:value} = ref(${2:null});\n</script>',
                documentation: 'Vue 3 setup script'
            }
        },
        'vanilla': {
            'webcomponent': {
                label: 'Web Component',
                insertText: 'class ${1:MyComponent} extends HTMLElement {\n\tstatic get observedAttributes() {\n\t\treturn [${2:\'attr\'}];\n\t}\n\n\tconstructor() {\n\t\tsuper();\n\t\tthis.attachShadow({ mode: \'open\' });\n\t}\n\n\tconnectedCallback() {\n\t\tthis.render();\n\t}\n\n\trender() {\n\t\tthis.shadowRoot.innerHTML = `\n\t\t\t<style>${3:/* styles */}</style>\n\t\t\t<div>${4:content}</div>\n\t\t`;\n\t}\n}\n\ncustomElements.define(\'${5:my-component}\', ${1:MyComponent});',
                documentation: 'Web Component class'
            }
        }
    };

    /**
     * VBP Code Editor
     */
    window.VBPCodeEditor = {
        editor: null,
        container: null,
        currentComponent: null,
        isMonacoLoaded: false,
        isVisible: false,
        autoSaveTimeout: null,
        changeListeners: [],
        config: {
            theme: 'vbp-dark',
            fontSize: 14,
            tabSize: 2,
            wordWrap: 'on',
            minimap: true,
            lineNumbers: true,
            autoComplete: true,
            linting: true,
            formatting: true,
            autoSave: true,
            autoSaveDelay: 2000
        },

        /**
         * Inicializar el editor
         */
        init: function() {
            if (this._vbpEditorInitialized) {
                return Promise.resolve();
            }

            this._vbpEditorInitialized = true;
            vbpLog.log('VBPCodeEditor: Initializing...');

            this.loadUserConfig();
            this.setupEventListeners();

            return this.loadMonaco();
        },

        /**
         * Cargar configuracion del usuario
         */
        loadUserConfig: function() {
            var savedConfig = localStorage.getItem('vbp_code_editor_config');
            if (savedConfig) {
                try {
                    var parsed = JSON.parse(savedConfig);
                    Object.assign(this.config, parsed);
                } catch (parseError) {
                    vbpLog.warn('VBPCodeEditor: Error parsing saved config');
                }
            }
        },

        /**
         * Guardar configuracion del usuario
         */
        saveUserConfig: function() {
            try {
                localStorage.setItem('vbp_code_editor_config', JSON.stringify(this.config));
            } catch (saveError) {
                vbpLog.warn('VBPCodeEditor: Error saving config');
            }
        },

        /**
         * Cargar Monaco Editor desde CDN
         */
        loadMonaco: function() {
            var self = this;

            if (this.isMonacoLoaded) {
                return Promise.resolve();
            }

            return new Promise(function(resolve, reject) {
                // Configurar rutas de Monaco
                window.require = window.require || {};
                window.require.paths = window.require.paths || {};
                window.require.paths['vs'] = MONACO_CONFIG.cdn;

                // Cargar loader
                var script = document.createElement('script');
                script.src = MONACO_CONFIG.cdn + '/loader.js';
                script.onload = function() {
                    // Cargar Monaco
                    window.require(['vs/editor/editor.main'], function() {
                        self.isMonacoLoaded = true;
                        self.registerThemes();
                        self.registerLanguageFeatures();
                        vbpLog.log('VBPCodeEditor: Monaco loaded successfully');
                        resolve();
                    });
                };
                script.onerror = function(error) {
                    vbpLog.error('VBPCodeEditor: Failed to load Monaco', error);
                    reject(error);
                };
                document.head.appendChild(script);
            });
        },

        /**
         * Registrar temas personalizados
         */
        registerThemes: function() {
            if (!window.monaco) return;

            Object.keys(MONACO_CONFIG.themes).forEach(function(themeId) {
                var theme = MONACO_CONFIG.themes[themeId];
                if (theme.rules || theme.colors) {
                    monaco.editor.defineTheme(themeId, {
                        base: theme.base,
                        inherit: true,
                        rules: theme.rules || [],
                        colors: theme.colors || {}
                    });
                }
            });
        },

        /**
         * Registrar features de lenguaje
         */
        registerLanguageFeatures: function() {
            if (!window.monaco) return;

            var self = this;

            // Configurar JavaScript/TypeScript para JSX
            monaco.languages.typescript.javascriptDefaults.setCompilerOptions({
                target: monaco.languages.typescript.ScriptTarget.Latest,
                allowNonTsExtensions: true,
                moduleResolution: monaco.languages.typescript.ModuleResolutionKind.NodeJs,
                module: monaco.languages.typescript.ModuleKind.CommonJS,
                noEmit: true,
                esModuleInterop: true,
                jsx: monaco.languages.typescript.JsxEmit.React,
                reactNamespace: 'React',
                allowJs: true,
                typeRoots: ['node_modules/@types']
            });

            // Agregar tipos de React
            monaco.languages.typescript.javascriptDefaults.addExtraLib(
                'declare const React: typeof import("react");\n' +
                'declare const ReactDOM: typeof import("react-dom");\n' +
                'declare const Vue: any;\n',
                'globals.d.ts'
            );

            // Registrar snippets
            Object.keys(CODE_SNIPPETS).forEach(function(framework) {
                var language = LANGUAGE_MAP[framework] || 'javascript';
                var snippets = CODE_SNIPPETS[framework];

                monaco.languages.registerCompletionItemProvider(language, {
                    provideCompletionItems: function() {
                        var suggestions = Object.keys(snippets).map(function(key) {
                            var snippet = snippets[key];
                            return {
                                label: snippet.label,
                                kind: monaco.languages.CompletionItemKind.Snippet,
                                insertText: snippet.insertText,
                                insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                                documentation: snippet.documentation
                            };
                        });
                        return { suggestions: suggestions };
                    }
                });
            });
        },

        /**
         * Configurar event listeners
         */
        setupEventListeners: function() {
            var self = this;

            // Escuchar evento de abrir editor
            document.addEventListener('vbp:open-code-editor', function(event) {
                if (event.detail && event.detail.component) {
                    self.open(event.detail.component);
                }
            });

            // Escuchar evento de cerrar editor
            document.addEventListener('vbp:close-code-editor', function() {
                self.close();
            });

            // Atajos de teclado globales
            document.addEventListener('keydown', function(event) {
                if (!self.isVisible) return;

                // Cmd/Ctrl + S: Guardar
                if ((event.metaKey || event.ctrlKey) && event.key === 's') {
                    event.preventDefault();
                    self.saveCurrentComponent();
                }

                // Cmd/Ctrl + Enter: Preview
                if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
                    event.preventDefault();
                    self.runPreview();
                }

                // Escape: Cerrar
                if (event.key === 'Escape' && !self.hasUnsavedChanges()) {
                    self.close();
                }

                // Cmd/Ctrl + Shift + F: Formatear
                if ((event.metaKey || event.ctrlKey) && event.shiftKey && event.key === 'f') {
                    event.preventDefault();
                    self.formatCode();
                }
            });
        },

        /**
         * Abrir editor con un componente
         *
         * @param {Object} component - Componente a editar
         */
        open: function(component) {
            var self = this;

            if (!component) {
                vbpLog.error('VBPCodeEditor: No component provided');
                return;
            }

            this.currentComponent = component;

            // Asegurar que Monaco esta cargado
            this.init().then(function() {
                self.createEditorUI();
                self.createEditor(component);
                self.isVisible = true;
            }).catch(function(error) {
                vbpLog.error('VBPCodeEditor: Failed to initialize', error);
            });
        },

        /**
         * Cerrar editor
         */
        close: function() {
            if (this.hasUnsavedChanges()) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }

            this.isVisible = false;
            this.currentComponent = null;

            if (this.editor) {
                this.editor.dispose();
                this.editor = null;
            }

            if (this.container) {
                this.container.classList.remove('visible');
                setTimeout(function() {
                    var containerToRemove = document.getElementById('vbp-code-editor-container');
                    if (containerToRemove) {
                        containerToRemove.remove();
                    }
                }, 300);
            }

            // Limpiar timeout de autosave
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
                this.autoSaveTimeout = null;
            }
        },

        /**
         * Crear UI del editor
         */
        createEditorUI: function() {
            var existing = document.getElementById('vbp-code-editor-container');
            if (existing) {
                existing.remove();
            }

            var component = this.currentComponent;
            var framework = component.framework || 'react';

            var containerHtml = '' +
                '<div id="vbp-code-editor-container" class="vbp-code-editor-container">' +
                    '<div class="vbp-code-editor-header">' +
                        '<div class="vbp-code-editor-title">' +
                            '<span class="vbp-code-editor-icon">' + this.getFrameworkIcon(framework) + '</span>' +
                            '<input type="text" class="vbp-code-editor-name" value="' + (component.name || '').replace(/"/g, '&quot;') + '" placeholder="Component name">' +
                            '<span class="vbp-code-editor-framework-badge">' + framework.toUpperCase() + '</span>' +
                        '</div>' +
                        '<div class="vbp-code-editor-actions">' +
                            '<button type="button" class="vbp-code-editor-btn" data-action="format" title="Format (Cmd+Shift+F)">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>' +
                            '</button>' +
                            '<button type="button" class="vbp-code-editor-btn" data-action="preview" title="Preview (Cmd+Enter)">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 19,12 5,21"/></svg>' +
                            '</button>' +
                            '<button type="button" class="vbp-code-editor-btn vbp-code-editor-btn-primary" data-action="save" title="Save (Cmd+S)">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>' +
                                ' Save' +
                            '</button>' +
                            '<button type="button" class="vbp-code-editor-btn" data-action="export" title="Export">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7,10 12,15 17,10"/><path d="M12 15V3"/></svg>' +
                            '</button>' +
                            '<button type="button" class="vbp-code-editor-btn vbp-code-editor-btn-close" data-action="close" title="Close (Esc)">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="vbp-code-editor-body">' +
                        '<div class="vbp-code-editor-sidebar">' +
                            '<div class="vbp-code-editor-props-panel">' +
                                '<div class="vbp-code-editor-panel-header">' +
                                    '<span>Props</span>' +
                                    '<button type="button" class="vbp-code-editor-btn-small" data-action="add-prop" title="Add prop">' +
                                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>' +
                                    '</button>' +
                                '</div>' +
                                '<div class="vbp-code-editor-props-list" id="vbp-props-list">' +
                                    this.renderPropsList(component.props) +
                                '</div>' +
                            '</div>' +
                            '<div class="vbp-code-editor-templates-panel">' +
                                '<div class="vbp-code-editor-panel-header">' +
                                    '<span>Templates</span>' +
                                '</div>' +
                                '<div class="vbp-code-editor-templates-list" id="vbp-templates-list">' +
                                    this.renderTemplatesList(framework) +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="vbp-code-editor-main">' +
                            '<div class="vbp-code-editor-tabs">' +
                                '<button type="button" class="vbp-code-editor-tab active" data-tab="code">Code</button>' +
                                '<button type="button" class="vbp-code-editor-tab" data-tab="styles">Styles</button>' +
                                '<button type="button" class="vbp-code-editor-tab" data-tab="settings">Settings</button>' +
                            '</div>' +
                            '<div class="vbp-code-editor-content">' +
                                '<div id="vbp-monaco-container" class="vbp-monaco-container"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="vbp-code-editor-preview">' +
                            '<div class="vbp-code-editor-panel-header">' +
                                '<span>Preview</span>' +
                                '<button type="button" class="vbp-code-editor-btn-small" data-action="refresh-preview" title="Refresh">' +
                                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>' +
                                '</button>' +
                            '</div>' +
                            '<div id="vbp-code-preview-' + component.id + '" class="vbp-code-preview-container"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="vbp-code-editor-statusbar">' +
                        '<div class="vbp-code-editor-status-left">' +
                            '<span class="vbp-code-editor-status-item" id="vbp-editor-cursor-pos">Ln 1, Col 1</span>' +
                            '<span class="vbp-code-editor-status-item" id="vbp-editor-language">' + this.getLanguageLabel(framework) + '</span>' +
                        '</div>' +
                        '<div class="vbp-code-editor-status-right">' +
                            '<span class="vbp-code-editor-status-item" id="vbp-editor-save-status">Saved</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            document.body.insertAdjacentHTML('beforeend', containerHtml);
            this.container = document.getElementById('vbp-code-editor-container');

            // Animar entrada
            requestAnimationFrame(function() {
                var container = document.getElementById('vbp-code-editor-container');
                if (container) {
                    container.classList.add('visible');
                }
            });

            this.bindUIEvents();
        },

        /**
         * Crear instancia del editor Monaco
         *
         * @param {Object} component - Componente
         */
        createEditor: function(component) {
            var self = this;
            var monacoContainer = document.getElementById('vbp-monaco-container');

            if (!monacoContainer || !window.monaco) {
                vbpLog.error('VBPCodeEditor: Monaco container or Monaco not available');
                return;
            }

            var language = LANGUAGE_MAP[component.framework] || 'javascript';

            this.editor = monaco.editor.create(monacoContainer, {
                value: component.code || '',
                language: language,
                theme: this.config.theme,
                fontSize: this.config.fontSize,
                tabSize: this.config.tabSize,
                wordWrap: this.config.wordWrap,
                minimap: {
                    enabled: this.config.minimap
                },
                lineNumbers: this.config.lineNumbers ? 'on' : 'off',
                automaticLayout: true,
                scrollBeyondLastLine: false,
                folding: true,
                glyphMargin: true,
                renderLineHighlight: 'all',
                bracketPairColorization: {
                    enabled: true
                },
                suggestOnTriggerCharacters: this.config.autoComplete,
                quickSuggestions: this.config.autoComplete,
                formatOnPaste: this.config.formatting,
                formatOnType: this.config.formatting
            });

            // Actualizar posicion del cursor en statusbar
            this.editor.onDidChangeCursorPosition(function(event) {
                var position = event.position;
                var cursorPosElement = document.getElementById('vbp-editor-cursor-pos');
                if (cursorPosElement) {
                    cursorPosElement.textContent = 'Ln ' + position.lineNumber + ', Col ' + position.column;
                }
            });

            // Detectar cambios
            this.editor.onDidChangeModelContent(function() {
                self.onCodeChange();
            });

            // Preview inicial
            this.runPreview();
        },

        /**
         * Handler de cambio de codigo
         */
        onCodeChange: function() {
            var self = this;

            // Actualizar status
            var statusElement = document.getElementById('vbp-editor-save-status');
            if (statusElement) {
                statusElement.textContent = 'Unsaved';
                statusElement.classList.add('unsaved');
            }

            // Auto-save con debounce
            if (this.config.autoSave) {
                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                }

                this.autoSaveTimeout = setTimeout(function() {
                    self.runPreview();
                }, this.config.autoSaveDelay);
            }

            // Notificar cambio
            if (this.currentComponent) {
                document.dispatchEvent(new CustomEvent('vbp:code-change', {
                    detail: {
                        componentId: this.currentComponent.id,
                        code: this.editor.getValue()
                    }
                }));
            }
        },

        /**
         * Bind de eventos de UI
         */
        bindUIEvents: function() {
            var self = this;
            var container = this.container;

            if (!container) return;

            // Botones de acciones
            container.querySelectorAll('[data-action]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var action = this.getAttribute('data-action');
                    self.handleAction(action);
                });
            });

            // Tabs
            container.querySelectorAll('.vbp-code-editor-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    self.switchTab(this.getAttribute('data-tab'));
                });
            });

            // Input de nombre
            var nameInput = container.querySelector('.vbp-code-editor-name');
            if (nameInput) {
                nameInput.addEventListener('change', function() {
                    if (self.currentComponent) {
                        self.currentComponent.name = this.value;
                    }
                });
            }

            // Props editables
            this.bindPropsEvents();
        },

        /**
         * Bind eventos de props
         */
        bindPropsEvents: function() {
            var self = this;
            var propsList = document.getElementById('vbp-props-list');

            if (!propsList) return;

            // Editar prop
            propsList.querySelectorAll('.vbp-prop-value').forEach(function(input) {
                input.addEventListener('change', function() {
                    var propName = this.getAttribute('data-prop');
                    var value = this.value;

                    // Intentar parsear como JSON para arrays/objects
                    try {
                        value = JSON.parse(value);
                    } catch (jsonParseError) {
                        // Mantener como string
                    }

                    if (self.currentComponent && window.VBPCodeComponents) {
                        window.VBPCodeComponents.updateProp(self.currentComponent.id, propName, value);
                    }
                });
            });

            // Eliminar prop
            propsList.querySelectorAll('[data-action="remove-prop"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var propName = this.getAttribute('data-prop');
                    if (confirm('Remove prop "' + propName + '"?')) {
                        if (self.currentComponent && window.VBPCodeComponents) {
                            window.VBPCodeComponents.removeProp(self.currentComponent.id, propName);
                            self.refreshPropsList();
                        }
                    }
                });
            });
        },

        /**
         * Manejar acciones
         *
         * @param {string} action - Accion a ejecutar
         */
        handleAction: function(action) {
            switch (action) {
                case 'save':
                    this.saveCurrentComponent();
                    break;
                case 'preview':
                case 'refresh-preview':
                    this.runPreview();
                    break;
                case 'format':
                    this.formatCode();
                    break;
                case 'export':
                    this.exportCurrentComponent();
                    break;
                case 'close':
                    this.close();
                    break;
                case 'add-prop':
                    this.showAddPropDialog();
                    break;
                default:
                    vbpLog.warn('VBPCodeEditor: Unknown action', action);
            }
        },

        /**
         * Cambiar tab
         *
         * @param {string} tabId - ID del tab
         */
        switchTab: function(tabId) {
            var container = this.container;
            if (!container) return;

            // Actualizar tabs activos
            container.querySelectorAll('.vbp-code-editor-tab').forEach(function(tab) {
                tab.classList.toggle('active', tab.getAttribute('data-tab') === tabId);
            });

            // Cambiar contenido del editor segun tab
            if (tabId === 'code' && this.editor && this.currentComponent) {
                this.editor.setValue(this.currentComponent.code || '');
                monaco.editor.setModelLanguage(this.editor.getModel(), LANGUAGE_MAP[this.currentComponent.framework] || 'javascript');
            } else if (tabId === 'styles' && this.editor && this.currentComponent) {
                this.editor.setValue(this.currentComponent.styles || '');
                monaco.editor.setModelLanguage(this.editor.getModel(), 'css');
            } else if (tabId === 'settings' && this.editor) {
                var settings = {
                    name: this.currentComponent.name,
                    category: this.currentComponent.category,
                    icon: this.currentComponent.icon,
                    defaultSize: this.currentComponent.defaultSize,
                    dependencies: this.currentComponent.dependencies
                };
                this.editor.setValue(JSON.stringify(settings, null, 2));
                monaco.editor.setModelLanguage(this.editor.getModel(), 'json');
            }
        },

        /**
         * Guardar componente actual
         */
        saveCurrentComponent: function() {
            var self = this;

            if (!this.currentComponent || !this.editor) {
                return;
            }

            // Actualizar codigo
            this.currentComponent.code = this.editor.getValue();

            // Guardar via API
            if (window.VBPCodeComponents) {
                window.VBPCodeComponents.saveComponent(this.currentComponent.id)
                    .then(function() {
                        var statusElement = document.getElementById('vbp-editor-save-status');
                        if (statusElement) {
                            statusElement.textContent = 'Saved';
                            statusElement.classList.remove('unsaved');
                        }
                    })
                    .catch(function(error) {
                        vbpLog.error('VBPCodeEditor: Save failed', error);
                    });
            }
        },

        /**
         * Ejecutar preview
         */
        runPreview: function() {
            if (!this.currentComponent || !this.editor) {
                return;
            }

            // Actualizar codigo temporalmente
            this.currentComponent.code = this.editor.getValue();

            // Generar preview
            if (window.VBPCodeComponents) {
                window.VBPCodeComponents.previewComponent(
                    this.currentComponent.id,
                    window.VBPCodeComponents.getDefaultProps(this.currentComponent)
                );
            }
        },

        /**
         * Formatear codigo
         */
        formatCode: function() {
            if (!this.editor) return;

            this.editor.getAction('editor.action.formatDocument').run();
        },

        /**
         * Exportar componente actual
         */
        exportCurrentComponent: function() {
            if (!this.currentComponent || !window.VBPCodeComponents) {
                return;
            }

            var exported = window.VBPCodeComponents.exportComponent(this.currentComponent.id, 'json');

            if (exported && exported.files) {
                // Crear blob y descargar
                var blob = new Blob([JSON.stringify(exported, null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = this.currentComponent.id + '.vbp-component.json';
                link.click();
                URL.revokeObjectURL(url);
            }
        },

        /**
         * Mostrar dialogo de agregar prop
         */
        showAddPropDialog: function() {
            var self = this;

            var dialogHtml = '' +
                '<div class="vbp-code-editor-dialog" id="vbp-add-prop-dialog">' +
                    '<div class="vbp-code-editor-dialog-content">' +
                        '<h3>Add New Prop</h3>' +
                        '<div class="vbp-form-group">' +
                            '<label>Name</label>' +
                            '<input type="text" id="vbp-new-prop-name" placeholder="propName">' +
                        '</div>' +
                        '<div class="vbp-form-group">' +
                            '<label>Type</label>' +
                            '<select id="vbp-new-prop-type">' +
                                '<option value="string">String</option>' +
                                '<option value="number">Number</option>' +
                                '<option value="boolean">Boolean</option>' +
                                '<option value="color">Color</option>' +
                                '<option value="select">Select</option>' +
                                '<option value="array">Array</option>' +
                                '<option value="object">Object</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="vbp-form-group">' +
                            '<label>Default Value</label>' +
                            '<input type="text" id="vbp-new-prop-default" placeholder="Default value">' +
                        '</div>' +
                        '<div class="vbp-form-group">' +
                            '<label>Label</label>' +
                            '<input type="text" id="vbp-new-prop-label" placeholder="Display label">' +
                        '</div>' +
                        '<div class="vbp-dialog-actions">' +
                            '<button type="button" class="vbp-btn" data-action="cancel">Cancel</button>' +
                            '<button type="button" class="vbp-btn vbp-btn-primary" data-action="add">Add Prop</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            document.body.insertAdjacentHTML('beforeend', dialogHtml);

            var dialog = document.getElementById('vbp-add-prop-dialog');

            dialog.querySelector('[data-action="cancel"]').addEventListener('click', function() {
                dialog.remove();
            });

            dialog.querySelector('[data-action="add"]').addEventListener('click', function() {
                var name = document.getElementById('vbp-new-prop-name').value.trim();
                var type = document.getElementById('vbp-new-prop-type').value;
                var defaultVal = document.getElementById('vbp-new-prop-default').value;
                var label = document.getElementById('vbp-new-prop-label').value || name;

                if (!name) {
                    alert('Prop name is required');
                    return;
                }

                // Parsear default value segun tipo
                var parsedDefault = defaultVal;
                if (type === 'number') {
                    parsedDefault = Number(defaultVal) || 0;
                } else if (type === 'boolean') {
                    parsedDefault = defaultVal === 'true';
                } else if (type === 'array' || type === 'object') {
                    try {
                        parsedDefault = JSON.parse(defaultVal);
                    } catch (jsonError) {
                        parsedDefault = type === 'array' ? [] : {};
                    }
                }

                if (self.currentComponent && window.VBPCodeComponents) {
                    window.VBPCodeComponents.addProp(self.currentComponent.id, {
                        name: name,
                        type: type,
                        default: parsedDefault,
                        label: label
                    });
                    self.refreshPropsList();
                }

                dialog.remove();
            });
        },

        /**
         * Refrescar lista de props
         */
        refreshPropsList: function() {
            var propsList = document.getElementById('vbp-props-list');
            if (propsList && this.currentComponent) {
                propsList.innerHTML = this.renderPropsList(this.currentComponent.props);
                this.bindPropsEvents();
            }
        },

        /**
         * Renderizar lista de props
         *
         * @param {Object} props - Props del componente
         * @returns {string} HTML
         */
        renderPropsList: function(props) {
            if (!props || Object.keys(props).length === 0) {
                return '<div class="vbp-props-empty">No props defined</div>';
            }

            var html = '';

            Object.keys(props).forEach(function(propName) {
                var prop = props[propName];
                var defaultValue = typeof prop.default === 'object' ? JSON.stringify(prop.default) : String(prop.default);

                html += '' +
                    '<div class="vbp-prop-item">' +
                        '<div class="vbp-prop-header">' +
                            '<span class="vbp-prop-name">' + propName + '</span>' +
                            '<span class="vbp-prop-type">' + (prop.type || 'string') + '</span>' +
                            '<button type="button" class="vbp-prop-remove" data-action="remove-prop" data-prop="' + propName + '" title="Remove">' +
                                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>' +
                            '</button>' +
                        '</div>' +
                        '<input type="text" class="vbp-prop-value" data-prop="' + propName + '" value="' + defaultValue.replace(/"/g, '&quot;') + '">' +
                    '</div>';
            });

            return html;
        },

        /**
         * Renderizar lista de templates
         *
         * @param {string} framework - Framework actual
         * @returns {string} HTML
         */
        renderTemplatesList: function(framework) {
            if (!window.VBPCodeComponents) {
                return '<div class="vbp-templates-loading">Loading...</div>';
            }

            var templates = window.VBPCodeComponents.getTemplates();
            var html = '';

            Object.keys(templates).forEach(function(templateId) {
                var template = templates[templateId];

                // Filtrar por framework
                if (template.framework !== framework) {
                    return;
                }

                html += '' +
                    '<div class="vbp-template-item" data-template="' + templateId + '">' +
                        '<span class="vbp-template-name">' + template.name + '</span>' +
                    '</div>';
            });

            if (!html) {
                html = '<div class="vbp-templates-empty">No templates for ' + framework + '</div>';
            }

            return html;
        },

        /**
         * Obtener icono del framework
         *
         * @param {string} framework - Framework
         * @returns {string} SVG icon
         */
        getFrameworkIcon: function(framework) {
            var icons = {
                'react': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><ellipse cx="12" cy="12" rx="10" ry="4"/><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(60 12 12)"/><ellipse cx="12" cy="12" rx="10" ry="4" transform="rotate(120 12 12)"/></svg>',
                'vue': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 22,22 2,22"/><polygon points="12,6 17,18 7,18"/></svg>',
                'svelte': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 4a4 4 0 00-4 4v8a4 4 0 01-4 4 4 4 0 01-4-4V8a4 4 0 014-4"/></svg>',
                'vanilla': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 3h-11L3 8l9 13 9-13-3.5-5z"/></svg>'
            };

            return icons[framework] || icons['vanilla'];
        },

        /**
         * Obtener label de lenguaje
         *
         * @param {string} framework - Framework
         * @returns {string}
         */
        getLanguageLabel: function(framework) {
            var labels = {
                'react': 'JavaScript (JSX)',
                'vue': 'Vue SFC',
                'svelte': 'Svelte',
                'vanilla': 'JavaScript'
            };

            return labels[framework] || 'JavaScript';
        },

        /**
         * Verificar si hay cambios sin guardar
         *
         * @returns {boolean}
         */
        hasUnsavedChanges: function() {
            if (!this.currentComponent || !this.editor) {
                return false;
            }

            return this.editor.getValue() !== (this.currentComponent._savedCode || this.currentComponent.code);
        },

        /**
         * Actualizar tema del editor
         *
         * @param {string} theme - ID del tema
         */
        setTheme: function(theme) {
            this.config.theme = theme;
            this.saveUserConfig();

            if (this.editor && window.monaco) {
                monaco.editor.setTheme(theme);
            }
        },

        /**
         * Actualizar tamano de fuente
         *
         * @param {number} size - Tamano en pixels
         */
        setFontSize: function(size) {
            this.config.fontSize = size;
            this.saveUserConfig();

            if (this.editor) {
                this.editor.updateOptions({ fontSize: size });
            }
        },

        /**
         * Toggle minimap
         *
         * @param {boolean} enabled - Habilitado
         */
        setMinimap: function(enabled) {
            this.config.minimap = enabled;
            this.saveUserConfig();

            if (this.editor) {
                this.editor.updateOptions({ minimap: { enabled: enabled } });
            }
        },

        /**
         * Agregar listener de cambios
         *
         * @param {Function} callback - Callback
         */
        onChange: function(callback) {
            if (typeof callback === 'function') {
                this.changeListeners.push(callback);
            }
        },

        /**
         * Remover listener de cambios
         *
         * @param {Function} callback - Callback
         */
        offChange: function(callback) {
            var index = this.changeListeners.indexOf(callback);
            if (index > -1) {
                this.changeListeners.splice(index, 1);
            }
        }
    };

    // Auto-inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Pre-inicializar para que Monaco se cargue en segundo plano
            if (window.VBPCodeEditor) {
                // No inicializar automaticamente, solo cuando se abra
            }
        });
    }

})();
