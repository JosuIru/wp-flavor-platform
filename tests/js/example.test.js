/**
 * Tests de ejemplo para Flavor Platform
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

describe('Flavor Platform - Tests de ejemplo', () => {

    describe('Configuracion global', () => {
        test('flavorChatIA esta definido', () => {
            expect(global.flavorChatIA).toBeDefined();
        });

        test('flavorChatIA tiene ajax_url', () => {
            expect(global.flavorChatIA.ajax_url).toBe('/wp-admin/admin-ajax.php');
        });

        test('flavorChatIA tiene nonce', () => {
            expect(global.flavorChatIA.nonce).toBeDefined();
            expect(typeof global.flavorChatIA.nonce).toBe('string');
        });

        test('wp.i18n.__ funciona correctamente', () => {
            expect(global.wp.i18n.__('Hello')).toBe('Hello');
        });

        test('wp.hooks esta disponible', () => {
            expect(global.wp.hooks).toBeDefined();
            expect(global.wp.hooks.addAction).toBeDefined();
            expect(global.wp.hooks.addFilter).toBeDefined();
        });
    });

    describe('LocalStorage mock', () => {
        beforeEach(() => {
            localStorage.clear();
        });

        test('setItem y getItem funcionan', () => {
            localStorage.setItem('testKey', 'testValue');
            expect(localStorage.getItem('testKey')).toBe('testValue');
        });

        test('removeItem funciona', () => {
            localStorage.setItem('testKey', 'testValue');
            localStorage.removeItem('testKey');
            expect(localStorage.getItem('testKey')).toBeNull();
        });
    });

    describe('Fetch mock', () => {
        test('fetch esta disponible', () => {
            expect(global.fetch).toBeDefined();
            expect(typeof global.fetch).toBe('function');
        });

        test('fetch retorna una promesa', async () => {
            const respuesta = await fetch('/api/test');
            expect(respuesta.ok).toBe(true);
        });
    });

    describe('FormData mock', () => {
        test('FormData se puede instanciar', () => {
            const formData = new FormData();
            expect(formData).toBeDefined();
        });

        test('FormData append y get funcionan', () => {
            const formData = new FormData();
            formData.append('campo', 'valor');
            expect(formData.get('campo')).toBe('valor');
        });
    });

    describe('jQuery mock', () => {
        test('jQuery esta disponible', () => {
            expect(global.jQuery).toBeDefined();
            expect(global.$).toBeDefined();
        });
    });

});
