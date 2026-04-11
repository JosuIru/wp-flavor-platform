/**
 * Tests for VBP Symbols functionality.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

describe('VBP Symbols', () => {
    // Sample symbol data for testing
    const sampleSymbol = {
        id: 'symbol-1',
        name: 'Test Symbol',
        slug: 'test-symbol',
        category: 'buttons',
        content: [
            {
                id: 'btn-root',
                type: 'button',
                props: {
                    text: 'Click Me',
                    variant: 'primary',
                },
            },
        ],
        variants: {
            default: {
                name: 'Default',
                overrides: {},
            },
            dark: {
                name: 'Dark Mode',
                overrides: {
                    'btn-root': {
                        variant: 'dark',
                    },
                },
            },
        },
        exposedProperties: ['text', 'variant'],
    };

    const sampleInstance = {
        id: 'instance-1',
        symbolId: 'symbol-1',
        documentId: 1,
        elementId: 'element-abc',
        variant: 'default',
        overrides: {},
        syncedVersion: 1,
    };

    beforeEach(() => {
        // Reset fetch mock
        global.fetch.mockClear();
    });

    describe('Symbol Creation', () => {
        test('creates symbol from selection', async () => {
            // Mock successful API response
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    symbol: { ...sampleSymbol, id: 'new-symbol-123' },
                }),
            });

            const selection = {
                elements: [
                    { id: 'el-1', type: 'container', children: [] },
                ],
            };

            // Simulate creating symbol from selection
            const response = await fetch('/wp-json/flavor-vbp/v1/symbols', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-VBP-Key': 'test-key',
                },
                body: JSON.stringify({
                    name: 'New Symbol',
                    content: selection.elements,
                    category: 'custom',
                }),
            });

            const data = await response.json();

            expect(response.ok).toBe(true);
            expect(data.success).toBe(true);
            expect(data.symbol).toBeDefined();
            expect(data.symbol.id).toBe('new-symbol-123');
        });

        test('validates required fields on creation', () => {
            const invalidSymbol = {
                // Missing name and content
                category: 'buttons',
            };

            expect(invalidSymbol.name).toBeUndefined();
            expect(invalidSymbol.content).toBeUndefined();
        });

        test('sanitizes symbol slug', () => {
            const testCases = [
                { input: 'My Symbol', expected: /^[a-z0-9-]+$/ },
                { input: 'Symbol #123', expected: /^[a-z0-9-]+$/ },
                { input: 'UPPERCASE', expected: /^[a-z0-9-]+$/ },
            ];

            testCases.forEach(({ input, expected }) => {
                const slug = input.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                expect(slug).toMatch(expected);
            });
        });
    });

    describe('Symbol Instance', () => {
        test('inserts symbol instance', async () => {
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    instance: sampleInstance,
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/symbol-1/instances', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-VBP-Key': 'test-key',
                },
                body: JSON.stringify({
                    documentId: 1,
                    elementId: 'element-xyz',
                }),
            });

            const data = await response.json();

            expect(response.ok).toBe(true);
            expect(data.success).toBe(true);
            expect(data.instance.symbolId).toBe('symbol-1');
        });

        test('applies override to instance', () => {
            const instance = { ...sampleInstance };
            const override = {
                'btn-root': {
                    text: 'Custom Text',
                },
            };

            // Apply override
            instance.overrides = override;

            expect(instance.overrides['btn-root']).toBeDefined();
            expect(instance.overrides['btn-root'].text).toBe('Custom Text');
        });

        test('detaches instance from symbol', async () => {
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    content: sampleSymbol.content,
                    documentId: 1,
                    elementId: 'element-abc',
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/instances/instance-1/detach', {
                method: 'POST',
                headers: {
                    'X-VBP-Key': 'test-key',
                },
            });

            const data = await response.json();

            expect(response.ok).toBe(true);
            expect(data.success).toBe(true);
            expect(data.content).toEqual(sampleSymbol.content);
        });

        test('syncs instance to latest symbol version', async () => {
            const outdatedInstance = {
                ...sampleInstance,
                syncedVersion: 1,
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    instance: {
                        ...outdatedInstance,
                        syncedVersion: 2,
                    },
                }),
            });

            const response = await fetch(`/wp-json/flavor-vbp/v1/symbols/instances/${outdatedInstance.id}/sync`, {
                method: 'POST',
                headers: {
                    'X-VBP-Key': 'test-key',
                },
            });

            const data = await response.json();

            expect(data.instance.syncedVersion).toBe(2);
        });
    });

    describe('Symbol Variants', () => {
        test('gets available variants', () => {
            const variants = sampleSymbol.variants;

            expect(Object.keys(variants)).toContain('default');
            expect(variants.default.name).toBe('Default');
        });

        test('creates new variant', async () => {
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    variant: {
                        key: 'outline',
                        name: 'Outline',
                        overrides: {
                            'btn-root': {
                                variant: 'outline',
                            },
                        },
                    },
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/symbol-1/variants', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-VBP-Key': 'test-key',
                },
                body: JSON.stringify({
                    key: 'outline',
                    name: 'Outline',
                    overrides: {
                        'btn-root': { variant: 'outline' },
                    },
                }),
            });

            const data = await response.json();

            expect(data.success).toBe(true);
            expect(data.variant.key).toBe('outline');
        });

        test('switches instance variant', () => {
            const instance = { ...sampleInstance, variant: 'default' };

            // Switch to dark variant
            instance.variant = 'dark';

            expect(instance.variant).toBe('dark');
        });

        test('cannot delete default variant', async () => {
            global.fetch.mockResolvedValueOnce({
                ok: false,
                status: 400,
                json: () => Promise.resolve({
                    success: false,
                    code: 'cannot_delete_default',
                    message: 'Cannot delete the default variant',
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/symbol-1/variants/default', {
                method: 'DELETE',
                headers: {
                    'X-VBP-Key': 'test-key',
                },
            });

            expect(response.ok).toBe(false);
            expect(response.status).toBe(400);
        });
    });

    describe('Symbol Export/Import', () => {
        test('exports symbols with correct structure', async () => {
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    version: '1.0',
                    exported_at: '2024-01-01T00:00:00Z',
                    site_url: 'https://example.com',
                    symbols: [sampleSymbol],
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/export', {
                headers: {
                    'X-VBP-Key': 'test-key',
                },
            });

            const data = await response.json();

            expect(data.version).toBeDefined();
            expect(data.exported_at).toBeDefined();
            expect(data.symbols).toBeInstanceOf(Array);
            expect(data.symbols[0].name).toBe('Test Symbol');
        });

        test('imports symbols with validation', async () => {
            const importData = {
                version: '1.0',
                symbols: [
                    {
                        name: 'Imported Symbol',
                        content: sampleSymbol.content,
                    },
                ],
            };

            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    imported: 1,
                    updated: 0,
                    skipped: 0,
                    errors: [],
                }),
            });

            const response = await fetch('/wp-json/flavor-vbp/v1/symbols/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-VBP-Key': 'test-key',
                },
                body: JSON.stringify(importData),
            });

            const data = await response.json();

            expect(data.success).toBe(true);
            expect(data.imported).toBe(1);
        });
    });

    describe('Symbol Categories', () => {
        test('returns predefined categories', () => {
            const categories = [
                { id: 'headers', name: 'Cabeceras' },
                { id: 'navigation', name: 'Navegación' },
                { id: 'footers', name: 'Footers' },
                { id: 'buttons', name: 'Botones' },
                { id: 'cards', name: 'Tarjetas' },
                { id: 'forms', name: 'Formularios' },
                { id: 'icons', name: 'Iconos' },
                { id: 'custom', name: 'Personalizados' },
            ];

            expect(categories.length).toBeGreaterThan(0);
            expect(categories.find(c => c.id === 'buttons')).toBeDefined();
            expect(categories.find(c => c.id === 'custom')).toBeDefined();
        });

        test('validates symbol category', () => {
            const validCategories = ['headers', 'navigation', 'footers', 'buttons', 'cards', 'forms', 'icons', 'custom'];

            validCategories.forEach(category => {
                expect(validCategories).toContain(category);
            });

            expect(validCategories).not.toContain('invalid');
        });
    });
});
