import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
    test: {
        // Test environment
        environment: 'jsdom',

        // Enable global test functions (describe, it, expect, etc.)
        globals: true,

        // Test file patterns
        include: ['tests/js/**/*.test.js', 'tests/js/**/*.spec.js'],
        exclude: ['**/node_modules/**', '**/dist/**'],

        // Setup files to run before each test file
        setupFiles: ['tests/js/setup.js'],

        // Coverage configuration
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html'],
            reportsDirectory: 'tests/coverage/js',
            include: ['assets/vbp/js/**/*.js'],
            exclude: [
                'assets/vbp/js/**/*.min.js',
                'assets/vbp/build/**',
                'assets/vbp/vendor/**',
            ],
        },

        // Timeouts
        testTimeout: 10000,
        hookTimeout: 10000,

        // Reporter
        reporters: ['verbose'],

        // Mock configuration
        mockReset: true,
        restoreMocks: true,

        // Watch mode
        watch: false,
    },

    resolve: {
        alias: {
            '@vbp': path.resolve(__dirname, 'assets/vbp/js'),
            '@modules': path.resolve(__dirname, 'assets/vbp/js/modules'),
        },
    },
});
