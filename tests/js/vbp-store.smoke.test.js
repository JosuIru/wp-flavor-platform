/**
 * Smoke tests para el store de Visual Builder Pro.
 *
 * El archivo histórico `vbp-store.test.js` es un runner manual de navegador,
 * no una suite Jest. Esta prueba valida el registro del store y unas pocas
 * operaciones reales del script cargado en JSDOM.
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

describe('Visual Builder Pro store smoke', () => {
    const sourcePath = '../../assets/vbp/js/vbp-store.js';

    function bootStore() {
        jest.resetModules();
        document.body.innerHTML = '';

        const stores = {};
        const alpine = {
            store(name, value) {
                if (typeof value !== 'undefined') {
                    stores[name] = value;
                    return value;
                }

                return stores[name];
            }
        };

        global.Alpine = alpine;
        window.Alpine = alpine;

        window.VBP_DEBUG = false;
        window.VBPPerformance = {
            debounce: (fn) => fn,
            deepClone: (value) => JSON.parse(JSON.stringify(value))
        };
        window.VBPStoreCatalog = {
            getDefaultName: (type) => type,
            getDefaultData: () => ({}),
            getDefaultStyles: () => ({}),
            getDefaultVariant: () => 'default',
            getVariantsForType: () => []
        };

        require(sourcePath);
        document.dispatchEvent(new Event('alpine:init'));

        return alpine.store('vbp');
    }

    test('registra el store vbp con estado base', () => {
        const store = bootStore();

        expect(store).toBeDefined();
        expect(Array.isArray(store.elements)).toBe(true);
        expect(store.selection.elementIds).toEqual([]);
        expect(store.zoom).toBe(100);
        expect(store.devicePreview).toBe('desktop');
    });

    test('inicializa, consulta y actualiza elementos', () => {
        const store = bootStore();

        store.initElements([
            { id: 'elemento-1', type: 'text', name: 'Inicial', styles: {}, children: [] }
        ]);

        expect(store.getElement('elemento-1').name).toBe('Inicial');

        store.updateElement('elemento-1', { name: 'Actualizado' });

        expect(store.getElement('elemento-1').name).toBe('Actualizado');
        expect(store.isDirty).toBe(true);
    });

    test('gestiona seleccion e inspector mode', () => {
        const store = bootStore();

        store.setSelection(['uno', 'dos']);
        expect(store.selection.elementIds).toEqual(['uno', 'dos']);

        store.clearSelection();
        expect(store.selection.elementIds).toEqual([]);

        store.setInspectorMode('advanced');
        expect(store.inspectorMode).toBe('advanced');

        store.toggleInspectorMode();
        expect(store.inspectorMode).toBe('basic');
    });

    test('sincroniza breakpoint activo con device preview', () => {
        const store = bootStore();

        store.setActiveBreakpoint('tablet');
        expect(store.activeBreakpoint).toBe('tablet');
        expect(store.devicePreview).toBe('tablet');

        store.setActiveBreakpoint('mobile');
        expect(store.activeBreakpoint).toBe('mobile');
        expect(store.devicePreview).toBe('mobile');
    });
});
