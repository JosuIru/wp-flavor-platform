/**
 * Jest/Vitest Setup File para Flavor Platform
 *
 * Configura el entorno global para tests de JavaScript
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

// Detect test framework (Jest or Vitest)
const isVitest = typeof vi !== 'undefined';
const mockFn = isVitest ? vi.fn : (typeof jest !== 'undefined' ? jest.fn : () => () => {});

// Mock de jQuery (conditional require for Node.js environment)
if (typeof require !== 'undefined') {
    try {
        global.jQuery = global.$ = require('jquery');
    } catch (e) {
        // jQuery not available, create minimal mock
        global.jQuery = global.$ = mockFn(() => ({
            on: mockFn(),
            off: mockFn(),
            trigger: mockFn(),
            find: mockFn(() => global.$()),
            addClass: mockFn(() => global.$()),
            removeClass: mockFn(() => global.$()),
            toggleClass: mockFn(() => global.$()),
            attr: mockFn(),
            css: mockFn(),
            html: mockFn(),
            text: mockFn(),
            val: mockFn(),
            append: mockFn(),
            prepend: mockFn(),
            remove: mockFn(),
            empty: mockFn(),
            each: mockFn(),
            length: 0,
        }));
    }
}

// Mock de WordPress globals
global.wp = {
    ajax: {
        post: jest.fn(),
        send: jest.fn()
    },
    i18n: {
        __: (text) => text,
        _x: (text) => text,
        _n: (single, plural, number) => number === 1 ? single : plural,
        sprintf: (format, ...args) => {
            let index = 0;
            return format.replace(/%[sd]/g, () => args[index++] || '');
        }
    },
    hooks: {
        addAction: jest.fn(),
        addFilter: jest.fn(),
        doAction: jest.fn(),
        applyFilters: jest.fn((name, value) => value)
    },
    url: {
        addQueryArgs: (url, args) => {
            const parametrosUrl = new URLSearchParams(args);
            return url + (url.includes('?') ? '&' : '?') + parametrosUrl.toString();
        }
    }
};

// Mock de ajaxurl de WordPress
global.ajaxurl = '/wp-admin/admin-ajax.php';

// Mock de objeto flavorChatIA
global.flavorChatIA = {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'test-nonce-12345',
    user_id: 1,
    is_admin: true,
    site_url: 'http://localhost',
    plugin_url: 'http://localhost/wp-content/plugins/flavor-platform',
    i18n: {
        loading: 'Cargando...',
        error: 'Error',
        success: 'Exito',
        confirm: 'Confirmar',
        cancel: 'Cancelar'
    }
};

// Mock de localStorage
const almacenamiento = {};
global.localStorage = {
    getItem: jest.fn((key) => almacenamiento[key] || null),
    setItem: jest.fn((key, value) => { almacenamiento[key] = value; }),
    removeItem: jest.fn((key) => { delete almacenamiento[key]; }),
    clear: jest.fn(() => { Object.keys(almacenamiento).forEach(key => delete almacenamiento[key]); })
};

// Mock de sessionStorage
global.sessionStorage = {
    ...global.localStorage
};

// Mock de fetch
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({}),
        text: () => Promise.resolve('')
    })
);

// Mock de FormData
global.FormData = class FormData {
    constructor() {
        this.datos = {};
    }
    append(key, value) {
        this.datos[key] = value;
    }
    get(key) {
        return this.datos[key];
    }
    has(key) {
        return key in this.datos;
    }
    delete(key) {
        delete this.datos[key];
    }
    entries() {
        return Object.entries(this.datos);
    }
};

// Mock de console para evitar ruido en tests
global.console = {
    ...console,
    log: jest.fn(),
    debug: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
    error: jest.fn()
};

// Mock de window.matchMedia
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: jest.fn().mockImplementation(query => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn()
    }))
});

// Mock de IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
    constructor(callback) {
        this.callback = callback;
    }
    observe() { return null; }
    unobserve() { return null; }
    disconnect() { return null; }
};

// Mock de ResizeObserver
global.ResizeObserver = class ResizeObserver {
    constructor(callback) {
        this.callback = callback;
    }
    observe() { return null; }
    unobserve() { return null; }
    disconnect() { return null; }
};

// Mock de MutationObserver
global.MutationObserver = class MutationObserver {
    constructor(callback) {
        this.callback = callback;
    }
    observe() { return null; }
    disconnect() { return null; }
    takeRecords() { return []; }
};

// Mock de vbpData (VBP specific)
global.vbpData = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    restUrl: '/wp-json/flavor-vbp/v1/',
    nonce: 'test-nonce-12345',
    postId: 1,
    userId: 1,
    isAdmin: true,
    capabilities: {
        canEdit: true,
        canPublish: true,
        canDelete: true,
    },
    settings: {
        autosaveInterval: 60000,
        maxUndoSteps: 50,
    },
    i18n: {
        save: 'Save',
        publish: 'Publish',
        cancel: 'Cancel',
        undo: 'Undo',
        redo: 'Redo',
        delete: 'Delete',
        duplicate: 'Duplicate',
        loading: 'Loading...',
        saved: 'Saved',
        error: 'Error',
    },
};

// Mock Alpine.js
global.Alpine = {
    data: mockFn(),
    store: mockFn((name, data) => {
        if (data === undefined) {
            return global.Alpine._stores[name] || {};
        }
        global.Alpine._stores[name] = data;
    }),
    _stores: {},
    start: mockFn(),
    reactive: mockFn((obj) => obj),
    effect: mockFn((fn) => fn()),
    nextTick: mockFn((callback) => Promise.resolve().then(callback)),
};

// Mock requestAnimationFrame
global.requestAnimationFrame = mockFn((callback) => setTimeout(callback, 0));
global.cancelAnimationFrame = mockFn((id) => clearTimeout(id));

// Limpiar mocks despues de cada test
afterEach(() => {
    if (isVitest && typeof vi !== 'undefined') {
        vi.clearAllMocks();
    } else if (typeof jest !== 'undefined') {
        jest.clearAllMocks();
    }
    // Clean Alpine stores
    if (global.Alpine && global.Alpine._stores) {
        global.Alpine._stores = {};
    }
});

// Restaurar mocks despues de todos los tests
afterAll(() => {
    if (isVitest && typeof vi !== 'undefined') {
        vi.restoreAllMocks();
    } else if (typeof jest !== 'undefined') {
        jest.restoreAllMocks();
    }
});
