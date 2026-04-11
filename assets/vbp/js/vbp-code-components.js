/**
 * Visual Builder Pro - Code Components
 *
 * Sistema para crear, gestionar y renderizar componentes de codigo
 * con React, Vue, Svelte o JavaScript vanilla.
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
     * Tipos de props soportados para componentes de codigo
     */
    const PROP_TYPES = {
        'string': {
            input: 'text',
            default: '',
            validate: function(value) { return typeof value === 'string'; },
            serialize: function(value) { return String(value); }
        },
        'number': {
            input: 'number',
            default: 0,
            validate: function(value) { return !isNaN(Number(value)); },
            serialize: function(value) { return Number(value); }
        },
        'boolean': {
            input: 'toggle',
            default: false,
            validate: function(value) { return typeof value === 'boolean'; },
            serialize: function(value) { return Boolean(value); }
        },
        'color': {
            input: 'color',
            default: '#000000',
            validate: function(value) { return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(value); },
            serialize: function(value) { return String(value); }
        },
        'select': {
            input: 'select',
            default: '',
            options: [],
            validate: function(value, prop) {
                if (!prop.options || !Array.isArray(prop.options)) return true;
                return prop.options.some(function(opt) {
                    return opt.value === value || opt === value;
                });
            },
            serialize: function(value) { return String(value); }
        },
        'image': {
            input: 'image',
            default: '',
            validate: function(value) { return typeof value === 'string'; },
            serialize: function(value) { return String(value); }
        },
        'icon': {
            input: 'icon-picker',
            default: '',
            validate: function(value) { return typeof value === 'string'; },
            serialize: function(value) { return String(value); }
        },
        'array': {
            input: 'list',
            default: [],
            validate: function(value) { return Array.isArray(value); },
            serialize: function(value) { return Array.isArray(value) ? value : []; }
        },
        'object': {
            input: 'json',
            default: {},
            validate: function(value) { return typeof value === 'object' && value !== null; },
            serialize: function(value) { return typeof value === 'object' ? value : {}; }
        },
        'children': {
            input: 'slot',
            default: null,
            validate: function() { return true; },
            serialize: function(value) { return value; }
        },
        'function': {
            input: 'code',
            default: '() => {}',
            validate: function(value) { return typeof value === 'string'; },
            serialize: function(value) { return String(value); }
        },
        'richtext': {
            input: 'richtext',
            default: '',
            validate: function(value) { return typeof value === 'string'; },
            serialize: function(value) { return String(value); }
        },
        'url': {
            input: 'url',
            default: '',
            validate: function(value) {
                if (!value) return true;
                try {
                    new URL(value);
                    return true;
                } catch (urlError) {
                    return false;
                }
            },
            serialize: function(value) { return String(value); }
        }
    };

    /**
     * Frameworks soportados con sus configuraciones
     */
    const FRAMEWORKS = {
        'react': {
            name: 'React',
            version: '18',
            cdnUrl: 'https://esm.sh/react@18',
            cdnDomUrl: 'https://esm.sh/react-dom@18',
            extension: 'jsx',
            defaultTemplate: 'react-basic',
            runtimeIncludes: [
                'https://unpkg.com/react@18/umd/react.production.min.js',
                'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js'
            ]
        },
        'vue': {
            name: 'Vue',
            version: '3',
            cdnUrl: 'https://unpkg.com/vue@3/dist/vue.global.prod.js',
            extension: 'vue',
            defaultTemplate: 'vue-basic',
            runtimeIncludes: [
                'https://unpkg.com/vue@3/dist/vue.global.prod.js'
            ]
        },
        'svelte': {
            name: 'Svelte',
            version: '4',
            cdnUrl: 'https://esm.sh/svelte@4',
            extension: 'svelte',
            defaultTemplate: 'svelte-basic',
            runtimeIncludes: []
        },
        'vanilla': {
            name: 'Vanilla JS',
            version: 'ES6',
            extension: 'js',
            defaultTemplate: 'vanilla-basic',
            runtimeIncludes: []
        }
    };

    /**
     * Plantillas de componentes predefinidas
     */
    const COMPONENT_TEMPLATES = {
        'react-basic': {
            name: 'React Basic',
            framework: 'react',
            code: `function Component({ text = "Hello World" }) {
    return (
        <div className="code-component">
            {text}
        </div>
    );
}`,
            props: {
                text: { type: 'string', default: 'Hello World', label: 'Text' }
            }
        },
        'react-stateful': {
            name: 'React con Estado',
            framework: 'react',
            code: `function Component({ initialValue = 0, step = 1 }) {
    const [value, setValue] = React.useState(initialValue);

    return (
        <div className="code-component counter">
            <span className="counter-value">{value}</span>
            <button
                onClick={() => setValue(v => v - step)}
                className="counter-btn"
            >
                -
            </button>
            <button
                onClick={() => setValue(v => v + step)}
                className="counter-btn"
            >
                +
            </button>
        </div>
    );
}`,
            props: {
                initialValue: { type: 'number', default: 0, label: 'Initial Value' },
                step: { type: 'number', default: 1, label: 'Step' }
            }
        },
        'react-styled': {
            name: 'React con Estilos',
            framework: 'react',
            code: `function Component({
    text = "Styled Component",
    backgroundColor = "#6366f1",
    textColor = "#ffffff",
    padding = "16px 24px",
    borderRadius = "8px"
}) {
    const styles = {
        backgroundColor,
        color: textColor,
        padding,
        borderRadius,
        fontFamily: 'system-ui, sans-serif',
        display: 'inline-block'
    };

    return (
        <div style={styles} className="code-component styled">
            {text}
        </div>
    );
}`,
            props: {
                text: { type: 'string', default: 'Styled Component', label: 'Text' },
                backgroundColor: { type: 'color', default: '#6366f1', label: 'Background Color' },
                textColor: { type: 'color', default: '#ffffff', label: 'Text Color' },
                padding: { type: 'string', default: '16px 24px', label: 'Padding' },
                borderRadius: { type: 'string', default: '8px', label: 'Border Radius' }
            }
        },
        'react-interactive': {
            name: 'React Interactivo',
            framework: 'react',
            code: `function Component({
    items = ["Item 1", "Item 2", "Item 3"],
    title = "Interactive List"
}) {
    const [selected, setSelected] = React.useState(null);

    return (
        <div className="code-component interactive-list">
            <h3>{title}</h3>
            <ul>
                {items.map((item, index) => (
                    <li
                        key={index}
                        onClick={() => setSelected(index)}
                        className={selected === index ? 'selected' : ''}
                    >
                        {item}
                    </li>
                ))}
            </ul>
            {selected !== null && (
                <p>Selected: {items[selected]}</p>
            )}
        </div>
    );
}`,
            props: {
                title: { type: 'string', default: 'Interactive List', label: 'Title' },
                items: { type: 'array', default: ['Item 1', 'Item 2', 'Item 3'], label: 'Items' }
            }
        },
        'vue-basic': {
            name: 'Vue Basic',
            framework: 'vue',
            code: `<template>
    <div class="code-component">
        {{ text }}
    </div>
</template>

<script>
export default {
    props: {
        text: {
            type: String,
            default: 'Hello Vue'
        }
    }
}
</script>`,
            props: {
                text: { type: 'string', default: 'Hello Vue', label: 'Text' }
            }
        },
        'vue-stateful': {
            name: 'Vue con Estado',
            framework: 'vue',
            code: `<template>
    <div class="code-component counter">
        <span class="counter-value">{{ count }}</span>
        <button @click="decrement" class="counter-btn">-</button>
        <button @click="increment" class="counter-btn">+</button>
    </div>
</template>

<script>
export default {
    props: {
        initialValue: {
            type: Number,
            default: 0
        },
        step: {
            type: Number,
            default: 1
        }
    },
    data() {
        return {
            count: this.initialValue
        }
    },
    methods: {
        increment() {
            this.count += this.step;
        },
        decrement() {
            this.count -= this.step;
        }
    }
}
</script>`,
            props: {
                initialValue: { type: 'number', default: 0, label: 'Initial Value' },
                step: { type: 'number', default: 1, label: 'Step' }
            }
        },
        'vue-composition': {
            name: 'Vue Composition API',
            framework: 'vue',
            code: `<template>
    <div class="code-component">
        <input v-model="search" placeholder="Search..." />
        <ul>
            <li v-for="item in filteredItems" :key="item">
                {{ item }}
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => ['Apple', 'Banana', 'Cherry', 'Date']
    }
});

const search = ref('');

const filteredItems = computed(() => {
    return props.items.filter(item =>
        item.toLowerCase().includes(search.value.toLowerCase())
    );
});
</script>`,
            props: {
                items: { type: 'array', default: ['Apple', 'Banana', 'Cherry', 'Date'], label: 'Items' }
            }
        },
        'svelte-basic': {
            name: 'Svelte Basic',
            framework: 'svelte',
            code: `<script>
    export let text = "Hello Svelte";
</script>

<div class="code-component">
    {text}
</div>`,
            props: {
                text: { type: 'string', default: 'Hello Svelte', label: 'Text' }
            }
        },
        'svelte-stateful': {
            name: 'Svelte con Estado',
            framework: 'svelte',
            code: `<script>
    export let initialValue = 0;
    export let step = 1;

    let count = initialValue;

    function increment() {
        count += step;
    }

    function decrement() {
        count -= step;
    }
</script>

<div class="code-component counter">
    <span class="counter-value">{count}</span>
    <button on:click={decrement} class="counter-btn">-</button>
    <button on:click={increment} class="counter-btn">+</button>
</div>`,
            props: {
                initialValue: { type: 'number', default: 0, label: 'Initial Value' },
                step: { type: 'number', default: 1, label: 'Step' }
            }
        },
        'vanilla-basic': {
            name: 'Vanilla JS Basic',
            framework: 'vanilla',
            code: `class Component extends HTMLElement {
    static get observedAttributes() {
        return ['text'];
    }

    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }

    connectedCallback() {
        this.render();
    }

    attributeChangedCallback() {
        this.render();
    }

    render() {
        const text = this.getAttribute('text') || 'Hello World';
        this.shadowRoot.innerHTML = \`
            <style>
                .code-component {
                    font-family: system-ui, sans-serif;
                    padding: 16px;
                }
            </style>
            <div class="code-component">
                \${text}
            </div>
        \`;
    }
}

customElements.define('vbp-component', Component);`,
            props: {
                text: { type: 'string', default: 'Hello World', label: 'Text' }
            }
        },
        'vanilla-stateful': {
            name: 'Vanilla JS con Estado',
            framework: 'vanilla',
            code: `class Component extends HTMLElement {
    static get observedAttributes() {
        return ['initial-value', 'step'];
    }

    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._count = 0;
    }

    connectedCallback() {
        this._count = parseInt(this.getAttribute('initial-value') || '0');
        this.render();
        this.attachEventListeners();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'initial-value' && oldValue === null) {
            this._count = parseInt(newValue || '0');
            this.render();
        }
    }

    get step() {
        return parseInt(this.getAttribute('step') || '1');
    }

    increment() {
        this._count += this.step;
        this.updateDisplay();
    }

    decrement() {
        this._count -= this.step;
        this.updateDisplay();
    }

    updateDisplay() {
        const display = this.shadowRoot.querySelector('.counter-value');
        if (display) display.textContent = this._count;
    }

    attachEventListeners() {
        this.shadowRoot.querySelector('.btn-inc')
            .addEventListener('click', () => this.increment());
        this.shadowRoot.querySelector('.btn-dec')
            .addEventListener('click', () => this.decrement());
    }

    render() {
        this.shadowRoot.innerHTML = \`
            <style>
                .counter {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-family: system-ui, sans-serif;
                }
                .counter-value {
                    font-size: 24px;
                    min-width: 60px;
                    text-align: center;
                }
                button {
                    padding: 8px 16px;
                    cursor: pointer;
                }
            </style>
            <div class="code-component counter">
                <button class="btn-dec">-</button>
                <span class="counter-value">\${this._count}</span>
                <button class="btn-inc">+</button>
            </div>
        \`;
    }
}

customElements.define('vbp-counter', Component);`,
            props: {
                initialValue: { type: 'number', default: 0, label: 'Initial Value' },
                step: { type: 'number', default: 1, label: 'Step' }
            }
        }
    };

    /**
     * Categorias de componentes de codigo
     */
    const CATEGORIES = {
        'interactive': {
            name: 'Interactivo',
            icon: 'pointer',
            description: 'Componentes con interacciones del usuario'
        },
        'display': {
            name: 'Visualizacion',
            icon: 'eye',
            description: 'Componentes para mostrar datos'
        },
        'form': {
            name: 'Formulario',
            icon: 'edit-3',
            description: 'Componentes de entrada de datos'
        },
        'layout': {
            name: 'Layout',
            icon: 'layout',
            description: 'Componentes de estructura'
        },
        'media': {
            name: 'Media',
            icon: 'image',
            description: 'Componentes multimedia'
        },
        'data': {
            name: 'Datos',
            icon: 'database',
            description: 'Componentes de visualizacion de datos'
        },
        'navigation': {
            name: 'Navegacion',
            icon: 'navigation',
            description: 'Componentes de navegacion'
        },
        'utility': {
            name: 'Utilidad',
            icon: 'tool',
            description: 'Componentes utilitarios'
        },
        'custom': {
            name: 'Personalizado',
            icon: 'code',
            description: 'Componentes personalizados'
        }
    };

    /**
     * Dependencias NPM permitidas (lista blanca)
     */
    const ALLOWED_DEPENDENCIES = {
        // React ecosystem
        'react': { version: '^18.0.0', cdn: 'https://esm.sh/react@18' },
        'react-dom': { version: '^18.0.0', cdn: 'https://esm.sh/react-dom@18' },
        'framer-motion': { version: '^10.0.0', cdn: 'https://esm.sh/framer-motion@10' },
        '@headlessui/react': { version: '^1.7.0', cdn: 'https://esm.sh/@headlessui/react@1.7' },
        '@heroicons/react': { version: '^2.0.0', cdn: 'https://esm.sh/@heroicons/react@2' },
        'react-icons': { version: '^4.0.0', cdn: 'https://esm.sh/react-icons@4' },
        'classnames': { version: '^2.0.0', cdn: 'https://esm.sh/classnames@2' },
        'clsx': { version: '^2.0.0', cdn: 'https://esm.sh/clsx@2' },

        // Vue ecosystem
        'vue': { version: '^3.0.0', cdn: 'https://esm.sh/vue@3' },
        '@vueuse/core': { version: '^10.0.0', cdn: 'https://esm.sh/@vueuse/core@10' },

        // Animation
        'gsap': { version: '^3.0.0', cdn: 'https://esm.sh/gsap@3' },
        'animejs': { version: '^3.0.0', cdn: 'https://esm.sh/animejs@3' },
        'lottie-web': { version: '^5.0.0', cdn: 'https://esm.sh/lottie-web@5' },

        // Data viz
        'chart.js': { version: '^4.0.0', cdn: 'https://esm.sh/chart.js@4' },
        'd3': { version: '^7.0.0', cdn: 'https://esm.sh/d3@7' },

        // Utilities
        'lodash': { version: '^4.0.0', cdn: 'https://esm.sh/lodash@4' },
        'date-fns': { version: '^2.0.0', cdn: 'https://esm.sh/date-fns@2' },
        'dayjs': { version: '^1.0.0', cdn: 'https://esm.sh/dayjs@1' }
    };

    /**
     * Sistema principal de Code Components
     */
    window.VBPCodeComponents = {
        components: {},
        categories: CATEGORIES,
        propTypes: PROP_TYPES,
        frameworks: FRAMEWORKS,
        templates: COMPONENT_TEMPLATES,
        allowedDependencies: ALLOWED_DEPENDENCIES,
        editorInstance: null,
        previewIframes: new Map(),
        isInitialized: false,

        /**
         * Inicializar el sistema
         */
        init: function() {
            if (this.isInitialized) {
                return;
            }

            this.isInitialized = true;
            vbpLog.log('VBPCodeComponents: Initializing...');

            this.loadComponents();
            this.setupEventListeners();
            this.initAlpineStore();

            vbpLog.log('VBPCodeComponents: Initialized successfully');
        },

        /**
         * Inicializar Alpine store para code components
         */
        initAlpineStore: function() {
            var self = this;

            document.addEventListener('alpine:init', function() {
                if (Alpine.store('vbp')) {
                    // Extender el store existente
                    var vbpStore = Alpine.store('vbp');

                    vbpStore.codeComponents = {
                        components: {},
                        activeComponent: null,
                        editorVisible: false,
                        libraryVisible: false,
                        previewVisible: true,

                        /**
                         * Registrar un code component
                         */
                        register: function(componentId, config) {
                            return self.registerCodeComponent(componentId, config);
                        },

                        /**
                         * Eliminar registro de un code component
                         */
                        unregister: function(componentId) {
                            return self.unregisterCodeComponent(componentId);
                        },

                        /**
                         * Obtener un code component por ID
                         */
                        get: function(componentId) {
                            return self.getComponent(componentId);
                        },

                        /**
                         * Obtener todos los code components
                         */
                        getAll: function() {
                            return self.getAllComponents();
                        },

                        /**
                         * Crear nuevo code component
                         */
                        create: function(framework) {
                            return self.createComponent(framework);
                        },

                        /**
                         * Guardar code component
                         */
                        save: function(componentId) {
                            return self.saveComponent(componentId);
                        },

                        /**
                         * Eliminar code component
                         */
                        delete: function(componentId) {
                            return self.deleteComponent(componentId);
                        },

                        /**
                         * Preview de un code component
                         */
                        preview: function(componentId, props) {
                            return self.previewComponent(componentId, props);
                        },

                        /**
                         * Exportar code component
                         */
                        export: function(componentId, format) {
                            return self.exportComponent(componentId, format);
                        },

                        // Gestion de props
                        addProp: function(componentId, prop) {
                            return self.addProp(componentId, prop);
                        },

                        updateProp: function(componentId, propName, value) {
                            return self.updateProp(componentId, propName, value);
                        },

                        removeProp: function(componentId, propName) {
                            return self.removeProp(componentId, propName);
                        },

                        // UI
                        openEditor: function(componentId) {
                            this.activeComponent = componentId;
                            this.editorVisible = true;
                            self.openCodeEditor(componentId);
                        },

                        closeEditor: function() {
                            this.editorVisible = false;
                            self.closeCodeEditor();
                        },

                        openLibrary: function() {
                            this.libraryVisible = true;
                        },

                        closeLibrary: function() {
                            this.libraryVisible = false;
                        },

                        togglePreview: function() {
                            this.previewVisible = !this.previewVisible;
                        }
                    };
                }
            });
        },

        /**
         * Configurar event listeners
         */
        setupEventListeners: function() {
            var self = this;

            // Escuchar comandos del editor
            document.addEventListener('vbp:command', function(event) {
                var command = event.detail && event.detail.command;

                if (command === 'code-component:create') {
                    self.showCreateDialog();
                } else if (command === 'code-component:library') {
                    self.showLibrary();
                } else if (command === 'code-component:edit' && event.detail.componentId) {
                    self.openCodeEditor(event.detail.componentId);
                }
            });

            // Escuchar cambios en el codigo
            document.addEventListener('vbp:code-change', function(event) {
                if (event.detail && event.detail.componentId) {
                    self.updateComponentCode(event.detail.componentId, event.detail.code);
                }
            });
        },

        /**
         * Cargar componentes guardados desde la API
         */
        loadComponents: function() {
            var self = this;

            if (!window.VBP_Config || !window.VBP_Config.restUrl) {
                vbpLog.warn('VBPCodeComponents: REST URL not available');
                return;
            }

            fetch(VBP_Config.restUrl + 'code-components', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.components) {
                    data.components.forEach(function(comp) {
                        self.components[comp.id] = comp;
                    });
                    vbpLog.log('VBPCodeComponents: Loaded ' + Object.keys(self.components).length + ' components');
                }
            })
            .catch(function(error) {
                vbpLog.warn('VBPCodeComponents: Error loading components', error);
            });
        },

        /**
         * Registrar un code component
         *
         * @param {string} componentId - ID unico del componente
         * @param {Object} config - Configuracion del componente
         * @returns {Object|null} Componente registrado o null si falla
         */
        registerCodeComponent: function(componentId, config) {
            if (!componentId || typeof componentId !== 'string') {
                vbpLog.error('VBPCodeComponents: Invalid component ID');
                return null;
            }

            if (!config || typeof config !== 'object') {
                vbpLog.error('VBPCodeComponents: Invalid config');
                return null;
            }

            // Validar framework
            var framework = config.framework || 'react';
            if (!FRAMEWORKS[framework]) {
                vbpLog.warn('VBPCodeComponents: Unknown framework "' + framework + '", using react');
                framework = 'react';
            }

            // Sanitizar ID
            componentId = componentId.replace(/[^a-z0-9-_]/gi, '-').toLowerCase();

            // Validar y normalizar props
            var normalizedProps = {};
            if (config.props && typeof config.props === 'object') {
                Object.keys(config.props).forEach(function(propName) {
                    var prop = config.props[propName];
                    var propType = prop.type || 'string';

                    if (!PROP_TYPES[propType]) {
                        propType = 'string';
                    }

                    normalizedProps[propName] = {
                        type: propType,
                        default: prop.default !== undefined ? prop.default : PROP_TYPES[propType].default,
                        label: prop.label || propName,
                        description: prop.description || '',
                        required: !!prop.required,
                        options: prop.options || []
                    };
                });
            }

            // Crear estructura del componente
            var component = {
                id: componentId,
                name: config.name || componentId,
                framework: framework,
                code: config.code || '',
                props: normalizedProps,
                styles: config.styles || '',
                dependencies: this.validateDependencies(config.dependencies || {}),
                defaultSize: config.defaultSize || { width: 200, height: 100 },
                category: CATEGORIES[config.category] ? config.category : 'custom',
                icon: config.icon || 'code',
                version: config.version || '1.0.0',
                author: config.author || '',
                description: config.description || '',
                isLocal: config.isLocal !== false,
                createdAt: config.createdAt || new Date().toISOString(),
                updatedAt: new Date().toISOString()
            };

            // Registrar
            this.components[componentId] = component;

            // Notificar registro
            document.dispatchEvent(new CustomEvent('vbp:code-component-registered', {
                detail: { component: component }
            }));

            vbpLog.log('VBPCodeComponents: Registered component "' + componentId + '"');

            return component;
        },

        /**
         * Eliminar registro de un code component
         *
         * @param {string} componentId - ID del componente
         * @returns {boolean}
         */
        unregisterCodeComponent: function(componentId) {
            if (!this.components[componentId]) {
                return false;
            }

            delete this.components[componentId];

            // Limpiar preview si existe
            if (this.previewIframes.has(componentId)) {
                var iframe = this.previewIframes.get(componentId);
                if (iframe && iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
                this.previewIframes.delete(componentId);
            }

            document.dispatchEvent(new CustomEvent('vbp:code-component-unregistered', {
                detail: { componentId: componentId }
            }));

            return true;
        },

        /**
         * Obtener un componente por ID
         *
         * @param {string} componentId - ID del componente
         * @returns {Object|null}
         */
        getComponent: function(componentId) {
            return this.components[componentId] || null;
        },

        /**
         * Obtener todos los componentes
         *
         * @returns {Object}
         */
        getAllComponents: function() {
            return this.components;
        },

        /**
         * Obtener componentes por categoria
         *
         * @param {string} category - Categoria
         * @returns {Array}
         */
        getComponentsByCategory: function(category) {
            var componentsArray = [];

            Object.keys(this.components).forEach(function(componentId) {
                var comp = this.components[componentId];
                if (comp.category === category) {
                    componentsArray.push(comp);
                }
            }, this);

            return componentsArray;
        },

        /**
         * Crear nuevo componente desde template
         *
         * @param {string} framework - Framework a usar
         * @param {string} templateId - ID del template (opcional)
         * @returns {Object}
         */
        createComponent: function(framework, templateId) {
            framework = framework || 'react';
            templateId = templateId || FRAMEWORKS[framework].defaultTemplate;

            var template = COMPONENT_TEMPLATES[templateId] || COMPONENT_TEMPLATES['react-basic'];
            var componentId = 'cc-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);

            var config = {
                name: 'New ' + FRAMEWORKS[framework].name + ' Component',
                framework: framework,
                code: template.code,
                props: JSON.parse(JSON.stringify(template.props)),
                category: 'custom',
                isLocal: true
            };

            return this.registerCodeComponent(componentId, config);
        },

        /**
         * Guardar componente en el servidor
         *
         * @param {string} componentId - ID del componente
         * @returns {Promise}
         */
        saveComponent: function(componentId) {
            var self = this;
            var component = this.components[componentId];

            if (!component) {
                return Promise.reject(new Error('Component not found'));
            }

            return fetch(VBP_Config.restUrl + 'code-components', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                },
                body: JSON.stringify(component)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    component.updatedAt = new Date().toISOString();
                    if (data.id) {
                        component.serverId = data.id;
                    }
                    self.showToast('Component saved successfully', 'success');
                    return component;
                } else {
                    throw new Error(data.message || 'Error saving component');
                }
            })
            .catch(function(error) {
                self.showToast('Error: ' + error.message, 'error');
                throw error;
            });
        },

        /**
         * Eliminar componente
         *
         * @param {string} componentId - ID del componente
         * @returns {Promise}
         */
        deleteComponent: function(componentId) {
            var self = this;
            var component = this.components[componentId];

            if (!component) {
                return Promise.reject(new Error('Component not found'));
            }

            // Si tiene ID de servidor, eliminar en backend
            if (component.serverId) {
                return fetch(VBP_Config.restUrl + 'code-components/' + component.serverId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': VBP_Config.restNonce
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        self.unregisterCodeComponent(componentId);
                        self.showToast('Component deleted', 'success');
                        return true;
                    } else {
                        throw new Error(data.message || 'Error deleting component');
                    }
                });
            }

            // Componente solo local
            this.unregisterCodeComponent(componentId);
            this.showToast('Component deleted', 'success');
            return Promise.resolve(true);
        },

        /**
         * Actualizar codigo del componente
         *
         * @param {string} componentId - ID del componente
         * @param {string} code - Nuevo codigo
         */
        updateComponentCode: function(componentId, code) {
            var component = this.components[componentId];

            if (!component) {
                return;
            }

            component.code = code;
            component.updatedAt = new Date().toISOString();

            // Actualizar preview si esta visible
            this.previewComponent(componentId, this.getDefaultProps(component));
        },

        /**
         * Obtener props por defecto de un componente
         *
         * @param {Object} component - Componente
         * @returns {Object}
         */
        getDefaultProps: function(component) {
            var props = {};

            Object.keys(component.props || {}).forEach(function(propName) {
                props[propName] = component.props[propName].default;
            });

            return props;
        },

        /**
         * Validar dependencias contra lista blanca
         *
         * @param {Object} dependencies - Dependencias solicitadas
         * @returns {Object} Dependencias validadas
         */
        validateDependencies: function(dependencies) {
            var validated = {};

            Object.keys(dependencies || {}).forEach(function(pkg) {
                if (ALLOWED_DEPENDENCIES[pkg]) {
                    validated[pkg] = {
                        version: dependencies[pkg] || ALLOWED_DEPENDENCIES[pkg].version,
                        cdn: ALLOWED_DEPENDENCIES[pkg].cdn
                    };
                } else {
                    vbpLog.warn('VBPCodeComponents: Dependency "' + pkg + '" is not in the allowed list');
                }
            });

            return validated;
        },

        /**
         * Resolver URL de CDN para un paquete
         *
         * @param {string} pkg - Nombre del paquete
         * @param {string} version - Version (opcional)
         * @returns {string|null}
         */
        resolveImport: function(pkg, version) {
            if (ALLOWED_DEPENDENCIES[pkg]) {
                return ALLOWED_DEPENDENCIES[pkg].cdn + (version ? '@' + version : '');
            }
            return null;
        },

        /**
         * Generar HTML de preview para iframe sandboxed
         *
         * @param {Object} component - Componente
         * @param {Object} props - Props para el preview
         * @returns {string} HTML del preview
         */
        generatePreviewHTML: function(component, props) {
            var framework = FRAMEWORKS[component.framework] || FRAMEWORKS['react'];
            var runtimeScripts = '';

            // Incluir scripts de runtime del framework
            framework.runtimeIncludes.forEach(function(url) {
                runtimeScripts += '<script src="' + url + '"><\/script>\n';
            });

            // Incluir dependencias del componente
            Object.keys(component.dependencies || {}).forEach(function(pkg) {
                var dep = component.dependencies[pkg];
                if (dep.cdn) {
                    runtimeScripts += '<script type="module">\n';
                    runtimeScripts += 'import * as ' + pkg.replace(/[^a-zA-Z]/g, '_') + ' from "' + dep.cdn + '";\n';
                    runtimeScripts += 'window["' + pkg + '"] = ' + pkg.replace(/[^a-zA-Z]/g, '_') + ';\n';
                    runtimeScripts += '<\/script>\n';
                }
            });

            var previewHTML = '<!DOCTYPE html>\n' +
                '<html>\n' +
                '<head>\n' +
                '    <meta charset="UTF-8">\n' +
                '    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n' +
                '    <style>\n' +
                '        * { box-sizing: border-box; }\n' +
                '        body { margin: 0; padding: 16px; font-family: system-ui, sans-serif; }\n' +
                '        .code-component { /* Base styles */ }\n' +
                '        ' + (component.styles || '') + '\n' +
                '    </style>\n' +
                '    ' + runtimeScripts + '\n' +
                '</head>\n' +
                '<body>\n' +
                '    <div id="root"></div>\n' +
                '    <script>\n';

            // Generar codigo de renderizado segun framework
            if (component.framework === 'react') {
                previewHTML += this.generateReactPreview(component, props);
            } else if (component.framework === 'vue') {
                previewHTML += this.generateVuePreview(component, props);
            } else if (component.framework === 'svelte') {
                previewHTML += this.generateSveltePreview(component, props);
            } else {
                previewHTML += this.generateVanillaPreview(component, props);
            }

            previewHTML += '    <\/script>\n' +
                '</body>\n' +
                '</html>';

            return previewHTML;
        },

        /**
         * Generar preview para React
         */
        generateReactPreview: function(component, props) {
            var propsJson = JSON.stringify(props || {});

            return '(function() {\n' +
                '    try {\n' +
                '        ' + component.code + '\n' +
                '        \n' +
                '        var props = ' + propsJson + ';\n' +
                '        var root = ReactDOM.createRoot(document.getElementById("root"));\n' +
                '        root.render(React.createElement(Component, props));\n' +
                '    } catch (e) {\n' +
                '        document.getElementById("root").innerHTML = \n' +
                '            "<div style=\\"color:red;padding:16px;\\">Error: " + e.message + "</div>";\n' +
                '        console.error(e);\n' +
                '    }\n' +
                '})();\n';
        },

        /**
         * Generar preview para Vue
         */
        generateVuePreview: function(component, props) {
            var propsJson = JSON.stringify(props || {});

            // Extraer template y script de Vue SFC
            var templateMatch = component.code.match(/<template>([\s\S]*?)<\/template>/);
            var scriptMatch = component.code.match(/<script(?:\s+setup)?>([\s\S]*?)<\/script>/);

            var templateContent = templateMatch ? templateMatch[1].trim() : '<div>Error: No template</div>';

            return '(function() {\n' +
                '    try {\n' +
                '        var app = Vue.createApp({\n' +
                '            template: `' + templateContent.replace(/`/g, '\\`') + '`,\n' +
                '            data() {\n' +
                '                return ' + propsJson + ';\n' +
                '            }\n' +
                '        });\n' +
                '        app.mount("#root");\n' +
                '    } catch (e) {\n' +
                '        document.getElementById("root").innerHTML = \n' +
                '            "<div style=\\"color:red;padding:16px;\\">Error: " + e.message + "</div>";\n' +
                '        console.error(e);\n' +
                '    }\n' +
                '})();\n';
        },

        /**
         * Generar preview para Svelte (limitado sin compilador)
         */
        generateSveltePreview: function(component, props) {
            // Svelte requiere compilacion, mostrar mensaje informativo
            return 'document.getElementById("root").innerHTML = \n' +
                '    "<div style=\\"padding:16px;background:#fef3c7;color:#92400e;\\">" +\n' +
                '    "Svelte preview requires compilation. Use the export feature to test." +\n' +
                '    "</div>";\n';
        },

        /**
         * Generar preview para Vanilla JS
         */
        generateVanillaPreview: function(component, props) {
            var propsAttrs = '';
            Object.keys(props || {}).forEach(function(key) {
                var value = props[key];
                var attrName = key.replace(/[A-Z]/g, function(letterMayuscula) { return '-' + letterMayuscula.toLowerCase(); });
                propsAttrs += ' ' + attrName + '="' + String(value).replace(/"/g, '&quot;') + '"';
            });

            return '(function() {\n' +
                '    try {\n' +
                '        ' + component.code + '\n' +
                '        \n' +
                '        document.getElementById("root").innerHTML = \n' +
                '            "<vbp-component' + propsAttrs.replace(/"/g, '\\"') + '></vbp-component>";\n' +
                '    } catch (e) {\n' +
                '        document.getElementById("root").innerHTML = \n' +
                '            "<div style=\\"color:red;padding:16px;\\">Error: " + e.message + "</div>";\n' +
                '        console.error(e);\n' +
                '    }\n' +
                '})();\n';
        },

        /**
         * Preview de un componente en iframe sandboxed
         *
         * @param {string} componentId - ID del componente
         * @param {Object} props - Props para el preview
         * @returns {HTMLIFrameElement}
         */
        previewComponent: function(componentId, props) {
            var component = this.components[componentId];

            if (!component) {
                vbpLog.error('VBPCodeComponents: Component not found for preview');
                return null;
            }

            // Obtener o crear iframe
            var iframe = this.previewIframes.get(componentId);
            var container = document.getElementById('vbp-code-preview-' + componentId);

            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.className = 'vbp-code-preview-iframe';
                iframe.sandbox = 'allow-scripts';
                iframe.setAttribute('title', 'Component Preview: ' + component.name);
                this.previewIframes.set(componentId, iframe);
            }

            // Generar HTML del preview
            var previewHTML = this.generatePreviewHTML(component, props || this.getDefaultProps(component));
            iframe.srcdoc = previewHTML;

            // Agregar al contenedor si existe
            if (container && !container.contains(iframe)) {
                container.innerHTML = '';
                container.appendChild(iframe);
            }

            return iframe;
        },

        /**
         * Agregar prop a un componente
         *
         * @param {string} componentId - ID del componente
         * @param {Object} prop - Configuracion del prop
         */
        addProp: function(componentId, prop) {
            var component = this.components[componentId];

            if (!component || !prop || !prop.name) {
                return false;
            }

            var propType = prop.type || 'string';
            if (!PROP_TYPES[propType]) {
                propType = 'string';
            }

            component.props[prop.name] = {
                type: propType,
                default: prop.default !== undefined ? prop.default : PROP_TYPES[propType].default,
                label: prop.label || prop.name,
                description: prop.description || '',
                required: !!prop.required,
                options: prop.options || []
            };

            component.updatedAt = new Date().toISOString();
            return true;
        },

        /**
         * Actualizar valor de prop
         *
         * @param {string} componentId - ID del componente
         * @param {string} propName - Nombre del prop
         * @param {*} value - Nuevo valor
         */
        updateProp: function(componentId, propName, value) {
            var component = this.components[componentId];

            if (!component || !component.props[propName]) {
                return false;
            }

            component.props[propName].default = value;
            component.updatedAt = new Date().toISOString();

            // Actualizar preview
            this.previewComponent(componentId, this.getDefaultProps(component));
            return true;
        },

        /**
         * Eliminar prop de un componente
         *
         * @param {string} componentId - ID del componente
         * @param {string} propName - Nombre del prop
         */
        removeProp: function(componentId, propName) {
            var component = this.components[componentId];

            if (!component || !component.props[propName]) {
                return false;
            }

            delete component.props[propName];
            component.updatedAt = new Date().toISOString();
            return true;
        },

        /**
         * Exportar componente
         *
         * @param {string} componentId - ID del componente
         * @param {string} format - Formato de exportacion
         * @returns {Object}
         */
        exportComponent: function(componentId, format) {
            var component = this.components[componentId];

            if (!component) {
                return null;
            }

            format = format || 'json';
            var framework = FRAMEWORKS[component.framework];
            var extension = framework ? framework.extension : 'js';

            var exportData = {
                files: {},
                metadata: {
                    name: component.name,
                    version: component.version,
                    framework: component.framework,
                    exportedAt: new Date().toISOString()
                }
            };

            // Archivo principal del componente
            exportData.files['component.' + extension] = component.code;

            // Estilos si existen
            if (component.styles) {
                exportData.files['component.css'] = component.styles;
            }

            // package.json con dependencias
            var packageJson = {
                name: component.id,
                version: component.version,
                dependencies: {}
            };

            Object.keys(component.dependencies || {}).forEach(function(pkg) {
                packageJson.dependencies[pkg] = component.dependencies[pkg].version;
            });

            exportData.files['package.json'] = JSON.stringify(packageJson, null, 2);

            // Metadatos del componente VBP
            exportData.files['vbp-component.json'] = JSON.stringify({
                id: component.id,
                name: component.name,
                framework: component.framework,
                props: component.props,
                category: component.category,
                icon: component.icon,
                defaultSize: component.defaultSize
            }, null, 2);

            // Si el formato es zip, crear blob
            if (format === 'zip') {
                // Requiere libreria de zip - devolver datos para procesar externamente
                exportData.format = 'zip';
            } else if (format === 'files') {
                exportData.format = 'files';
            } else {
                exportData.format = 'json';
            }

            return exportData;
        },

        /**
         * Importar componente desde JSON
         *
         * @param {Object} data - Datos del componente
         * @returns {Object|null}
         */
        importComponent: function(data) {
            if (!data || !data.code) {
                vbpLog.error('VBPCodeComponents: Invalid import data');
                return null;
            }

            // Generar nuevo ID si no tiene
            var componentId = data.id || 'imported-' + Date.now();

            return this.registerCodeComponent(componentId, {
                name: data.name || 'Imported Component',
                framework: data.framework || 'react',
                code: data.code,
                props: data.props || {},
                styles: data.styles || '',
                dependencies: data.dependencies || {},
                category: data.category || 'custom',
                icon: data.icon || 'code',
                defaultSize: data.defaultSize || { width: 200, height: 100 }
            });
        },

        /**
         * Abrir editor de codigo
         *
         * @param {string} componentId - ID del componente
         */
        openCodeEditor: function(componentId) {
            var component = this.components[componentId];

            if (!component) {
                vbpLog.error('VBPCodeComponents: Component not found');
                return;
            }

            // Notificar a VBPCodeEditor si esta disponible
            if (window.VBPCodeEditor && window.VBPCodeEditor.open) {
                window.VBPCodeEditor.open(component);
            } else {
                // Fallback: emitir evento para que otro sistema lo maneje
                document.dispatchEvent(new CustomEvent('vbp:open-code-editor', {
                    detail: { component: component }
                }));
            }
        },

        /**
         * Cerrar editor de codigo
         */
        closeCodeEditor: function() {
            if (window.VBPCodeEditor && window.VBPCodeEditor.close) {
                window.VBPCodeEditor.close();
            }

            document.dispatchEvent(new CustomEvent('vbp:close-code-editor'));
        },

        /**
         * Mostrar dialogo de creacion
         */
        showCreateDialog: function() {
            document.dispatchEvent(new CustomEvent('vbp:show-modal', {
                detail: {
                    type: 'code-component-create',
                    data: {
                        frameworks: FRAMEWORKS,
                        templates: COMPONENT_TEMPLATES,
                        categories: CATEGORIES
                    }
                }
            }));
        },

        /**
         * Mostrar libreria de componentes
         */
        showLibrary: function() {
            document.dispatchEvent(new CustomEvent('vbp:show-modal', {
                detail: {
                    type: 'code-component-library',
                    data: {
                        components: this.components,
                        categories: CATEGORIES
                    }
                }
            }));
        },

        /**
         * Mostrar toast de notificacion
         *
         * @param {string} message - Mensaje
         * @param {string} type - Tipo (success, error, info)
         */
        showToast: function(message, type) {
            if (window.VBPToast && window.VBPToast.show) {
                window.VBPToast.show(message, type);
            } else {
                // Fallback
                document.dispatchEvent(new CustomEvent('vbp:toast', {
                    detail: { message: message, type: type || 'info' }
                }));
            }
        },

        /**
         * Obtener templates disponibles
         *
         * @returns {Object}
         */
        getTemplates: function() {
            return COMPONENT_TEMPLATES;
        },

        /**
         * Obtener frameworks disponibles
         *
         * @returns {Object}
         */
        getFrameworks: function() {
            return FRAMEWORKS;
        },

        /**
         * Obtener tipos de props
         *
         * @returns {Object}
         */
        getPropTypes: function() {
            return PROP_TYPES;
        },

        /**
         * Obtener categorias
         *
         * @returns {Object}
         */
        getCategories: function() {
            return CATEGORIES;
        },

        /**
         * Obtener dependencias permitidas
         *
         * @returns {Object}
         */
        getAllowedDependencies: function() {
            return ALLOWED_DEPENDENCIES;
        }
    };

    // Exponer globalmente para compatibilidad con VBP
    if (!window.VBP) {
        window.VBP = {};
    }

    /**
     * API publica para registrar code components
     */
    window.VBP.registerCodeComponent = function(componentId, config) {
        return window.VBPCodeComponents.registerCodeComponent(componentId, config);
    };

    // Auto-inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPCodeComponents.init();
        });
    } else {
        window.VBPCodeComponents.init();
    }

})();
