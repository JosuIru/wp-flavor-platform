/**
 * Jest Configuration para Flavor Platform
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

module.exports = {
    // Nombre del proyecto
    displayName: 'flavor-chat-ia',

    // Entorno de pruebas
    testEnvironment: 'jsdom',

    // Raiz de los tests
    roots: ['<rootDir>/tests/js'],

    // Patron de archivos de test
    testMatch: [
        '**/__tests__/**/*.js',
        '**/*.test.js',
        '**/*.spec.js'
    ],

    // Archivos a ignorar
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/dist/',
        '/mobile-apps/'
    ],

    // Transformaciones
    transform: {
        '^.+\\.js$': 'babel-jest'
    },

    // Modulos a ignorar en transformacion
    transformIgnorePatterns: [
        '/node_modules/'
    ],

    // Setup de pruebas
    setupFilesAfterEnv: [
        '<rootDir>/tests/js/setup.js'
    ],

    // Cobertura de codigo
    collectCoverageFrom: [
        'assets/js/**/*.js',
        'admin/js/**/*.js',
        '!**/*.min.js',
        '!**/node_modules/**'
    ],

    // Directorio de reportes de cobertura
    coverageDirectory: '<rootDir>/reports/coverage-js',

    // Umbrales de cobertura
    coverageThreshold: {
        global: {
            branches: 50,
            functions: 50,
            lines: 50,
            statements: 50
        }
    },

    // Formatos de reporte
    coverageReporters: ['text', 'lcov', 'html'],

    // Mocks globales
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy'
    },

    // Variables globales
    globals: {
        'wp': {},
        'jQuery': {},
        '$': {},
        'ajaxurl': '/wp-admin/admin-ajax.php',
        'flavorChatIA': {
            'ajax_url': '/wp-admin/admin-ajax.php',
            'nonce': 'test-nonce'
        }
    },

    // Verbose output
    verbose: true,

    // Timeout
    testTimeout: 10000,

    // Cache
    cacheDirectory: '<rootDir>/node_modules/.cache/jest'
};
