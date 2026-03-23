/**
 * Jest Setup File para Flavor Platform
 *
 * Configura el entorno global para tests de JavaScript
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

// Mock de jQuery
global.jQuery = global.$ = require('jquery');

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
    plugin_url: 'http://localhost/wp-content/plugins/flavor-chat-ia',
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

// Limpiar mocks despues de cada test
afterEach(() => {
    jest.clearAllMocks();
});

// Restaurar mocks despues de todos los tests
afterAll(() => {
    jest.restoreAllMocks();
});
