/**
 * Visual Builder Pro - Store Tests
 * Tests básicos para el estado global del editor
 *
 * Para ejecutar: Abrir tests/js/test-runner.html en el navegador
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    /**
     * Mini framework de testing
     */
    var TestRunner = {
        tests: [],
        passed: 0,
        failed: 0,

        describe: function(name, fn) {
            console.group('%c' + name, 'font-weight: bold; color: #333;');
            fn();
            console.groupEnd();
        },

        it: function(description, fn) {
            try {
                fn();
                this.passed++;
                console.log('%c✓ ' + description, 'color: green;');
            } catch (error) {
                this.failed++;
                console.log('%c✗ ' + description, 'color: red;');
                console.error('  Error:', error.message);
            }
        },

        assertEqual: function(actual, expected, message) {
            if (actual !== expected) {
                throw new Error(
                    (message || 'Assertion failed') +
                    ': expected ' + JSON.stringify(expected) +
                    ', got ' + JSON.stringify(actual)
                );
            }
        },

        assertDeepEqual: function(actual, expected, message) {
            if (JSON.stringify(actual) !== JSON.stringify(expected)) {
                throw new Error(
                    (message || 'Deep assertion failed') +
                    ': expected ' + JSON.stringify(expected) +
                    ', got ' + JSON.stringify(actual)
                );
            }
        },

        assertTrue: function(value, message) {
            if (!value) {
                throw new Error(message || 'Expected true, got false');
            }
        },

        assertFalse: function(value, message) {
            if (value) {
                throw new Error(message || 'Expected false, got true');
            }
        },

        assertDefined: function(value, message) {
            if (typeof value === 'undefined') {
                throw new Error(message || 'Expected value to be defined');
            }
        },

        run: function() {
            console.log('%c\n=== VBP Store Tests ===\n', 'font-size: 14px; font-weight: bold;');

            this.testPerformanceUtilities();
            this.testStoreInitialization();
            this.testElementOperations();
            this.testSelectionManagement();
            this.testHistoryOperations();
            this.testUIState();

            console.log('\n%c=== Resultados ===', 'font-size: 14px; font-weight: bold;');
            console.log('%cPasaron: ' + this.passed, 'color: green;');
            console.log('%cFallaron: ' + this.failed, 'color: red;');

            return this.failed === 0;
        },

        /**
         * Tests para utilidades de performance
         */
        testPerformanceUtilities: function() {
            var self = this;

            this.describe('VBPPerformance Utilities', function() {
                self.it('debounce debe existir', function() {
                    self.assertDefined(window.VBPPerformance);
                    self.assertDefined(window.VBPPerformance.debounce);
                });

                self.it('throttle debe existir', function() {
                    self.assertDefined(window.VBPPerformance.throttle);
                });

                self.it('memoize debe cachear resultados', function() {
                    var callCount = 0;
                    var expensiveFunction = function(x) {
                        callCount++;
                        return x * 2;
                    };

                    var memoized = window.VBPPerformance.memoize(expensiveFunction);

                    self.assertEqual(memoized(5), 10);
                    self.assertEqual(memoized(5), 10);
                    self.assertEqual(callCount, 1, 'Debe llamar la función solo una vez');
                });

                self.it('deepClone debe clonar objetos profundamente', function() {
                    var original = {
                        a: 1,
                        b: { c: 2, d: [1, 2, 3] }
                    };

                    var cloned = window.VBPPerformance.deepClone(original);

                    self.assertDeepEqual(cloned, original);

                    // Modificar el clon no debe afectar al original
                    cloned.b.c = 999;
                    self.assertEqual(original.b.c, 2);
                });

                self.it('shallowEqual debe comparar objetos superficialmente', function() {
                    var objA = { a: 1, b: 2 };
                    var objB = { a: 1, b: 2 };
                    var objC = { a: 1, b: 3 };

                    self.assertTrue(window.VBPPerformance.shallowEqual(objA, objB));
                    self.assertFalse(window.VBPPerformance.shallowEqual(objA, objC));
                });

                self.it('getVisibleRange debe calcular rango visible', function() {
                    var range = window.VBPPerformance.getVisibleRange(
                        100,  // scrollTop
                        500,  // containerHeight
                        50,   // itemHeight
                        100,  // totalItems
                        2     // buffer
                    );

                    self.assertDefined(range.startIndex);
                    self.assertDefined(range.endIndex);
                    self.assertDefined(range.offsetY);
                    self.assertTrue(range.startIndex >= 0);
                    self.assertTrue(range.endIndex <= 100);
                });
            });
        },

        /**
         * Tests para inicialización del store
         */
        testStoreInitialization: function() {
            var self = this;

            this.describe('Store Initialization', function() {
                self.it('Alpine.store debe existir', function() {
                    self.assertDefined(window.Alpine);
                    self.assertDefined(window.Alpine.store);
                });

                self.it('vbp store debe tener propiedades requeridas', function() {
                    var store = Alpine.store('vbp');
                    self.assertDefined(store);
                    self.assertDefined(store.elements);
                    self.assertDefined(store.selection);
                    self.assertDefined(store.history);
                    self.assertDefined(store.zoom);
                    self.assertDefined(store.devicePreview);
                });

                self.it('initElements debe inicializar elementos', function() {
                    var store = Alpine.store('vbp');
                    var testElements = [
                        { id: 'test_1', type: 'text', data: {} },
                        { id: 'test_2', type: 'hero', data: {} }
                    ];

                    store.initElements(testElements);

                    self.assertEqual(store.elements.length, 2);
                    self.assertEqual(store.elements[0].id, 'test_1');
                });
            });
        },

        /**
         * Tests para operaciones de elementos
         */
        testElementOperations: function() {
            var self = this;

            this.describe('Element Operations', function() {
                var store = Alpine.store('vbp');

                // Reset elementos para tests
                store.initElements([]);

                self.it('addElement debe añadir elemento', function() {
                    var initialCount = store.elements.length;

                    store.addElement({
                        id: 'el_add_test',
                        type: 'text',
                        name: 'Test Element',
                        data: { content: 'Hello' }
                    });

                    self.assertEqual(store.elements.length, initialCount + 1);
                });

                self.it('getElement debe encontrar elemento por ID', function() {
                    var element = store.getElement('el_add_test');

                    self.assertDefined(element);
                    self.assertEqual(element.id, 'el_add_test');
                    self.assertEqual(element.type, 'text');
                });

                self.it('updateElement debe actualizar propiedades', function() {
                    store.updateElement('el_add_test', {
                        name: 'Updated Name'
                    });

                    var element = store.getElement('el_add_test');
                    self.assertEqual(element.name, 'Updated Name');
                });

                self.it('duplicateElement debe crear copia', function() {
                    var countBefore = store.elements.length;

                    store.duplicateElement('el_add_test');

                    self.assertEqual(store.elements.length, countBefore + 1);

                    // El duplicado debe tener ID diferente
                    var lastElement = store.elements[store.elements.length - 1];
                    self.assertTrue(lastElement.id !== 'el_add_test');
                    self.assertEqual(lastElement.name, 'Updated Name (copia)');
                });

                self.it('removeElement debe eliminar elemento', function() {
                    var countBefore = store.elements.length;

                    store.removeElement('el_add_test');

                    self.assertEqual(store.elements.length, countBefore - 1);
                    self.assertEqual(store.getElement('el_add_test'), undefined);
                });

                self.it('moveElement debe reordenar elementos', function() {
                    // Añadir elementos para test
                    store.initElements([
                        { id: 'move_1', type: 'text' },
                        { id: 'move_2', type: 'text' },
                        { id: 'move_3', type: 'text' }
                    ]);

                    store.moveElement(0, 2);

                    self.assertEqual(store.elements[0].id, 'move_2');
                    self.assertEqual(store.elements[1].id, 'move_3');
                    self.assertEqual(store.elements[2].id, 'move_1');
                });
            });
        },

        /**
         * Tests para gestión de selección
         */
        testSelectionManagement: function() {
            var self = this;

            this.describe('Selection Management', function() {
                var store = Alpine.store('vbp');

                store.initElements([
                    { id: 'sel_1', type: 'text' },
                    { id: 'sel_2', type: 'hero' },
                    { id: 'sel_3', type: 'button' }
                ]);

                self.it('selectElement debe seleccionar elemento', function() {
                    store.selectElement('sel_1');

                    self.assertEqual(store.selection.elementIds.length, 1);
                    self.assertEqual(store.selection.elementIds[0], 'sel_1');
                });

                self.it('selectElement con multi debe añadir a selección', function() {
                    store.selectElement('sel_2', true);

                    self.assertEqual(store.selection.elementIds.length, 2);
                    self.assertTrue(store.selection.elementIds.includes('sel_1'));
                    self.assertTrue(store.selection.elementIds.includes('sel_2'));
                });

                self.it('isSelected debe verificar selección', function() {
                    self.assertTrue(store.isSelected('sel_1'));
                    self.assertTrue(store.isSelected('sel_2'));
                    self.assertFalse(store.isSelected('sel_3'));
                });

                self.it('deselectAll debe limpiar selección', function() {
                    store.deselectAll();

                    self.assertEqual(store.selection.elementIds.length, 0);
                });

                self.it('selectAll debe seleccionar todos', function() {
                    store.selectAll();

                    self.assertEqual(store.selection.elementIds.length, 3);
                });

                self.it('getSelectedElements debe retornar elementos seleccionados', function() {
                    store.deselectAll();
                    store.selectElement('sel_1');
                    store.selectElement('sel_3', true);

                    var selected = store.getSelectedElements();

                    self.assertEqual(selected.length, 2);
                });
            });
        },

        /**
         * Tests para operaciones de historial
         */
        testHistoryOperations: function() {
            var self = this;

            this.describe('History Operations (Undo/Redo)', function() {
                var store = Alpine.store('vbp');

                // Reset
                store.initElements([]);
                store.history.past = [];
                store.history.future = [];

                self.it('pushHistory debe guardar estado', function() {
                    store.addElement({ id: 'hist_1', type: 'text' });
                    store.pushHistory();

                    self.assertTrue(store.history.past.length > 0);
                });

                self.it('canUndo debe indicar si se puede deshacer', function() {
                    self.assertTrue(store.canUndo());
                });

                self.it('undo debe restaurar estado anterior', function() {
                    store.addElement({ id: 'hist_2', type: 'text' });
                    store.pushHistory();

                    var countBefore = store.elements.length;
                    store.undo();

                    // Después de undo, debería tener un elemento menos
                    self.assertEqual(store.elements.length, countBefore - 1);
                });

                self.it('canRedo debe indicar si se puede rehacer', function() {
                    self.assertTrue(store.canRedo());
                });

                self.it('redo debe restaurar estado siguiente', function() {
                    var countBefore = store.elements.length;
                    store.redo();

                    self.assertEqual(store.elements.length, countBefore + 1);
                });
            });
        },

        /**
         * Tests para estado de UI
         */
        testUIState: function() {
            var self = this;

            this.describe('UI State', function() {
                var store = Alpine.store('vbp');

                self.it('setZoom debe actualizar zoom', function() {
                    store.setZoom(75);
                    self.assertEqual(store.zoom, 75);

                    store.setZoom(150);
                    self.assertEqual(store.zoom, 150);
                });

                self.it('setZoom debe respetar límites', function() {
                    store.setZoom(10);
                    self.assertTrue(store.zoom >= 25);

                    store.setZoom(500);
                    self.assertTrue(store.zoom <= 200);
                });

                self.it('setDevicePreview debe cambiar dispositivo', function() {
                    store.setDevicePreview('tablet');
                    self.assertEqual(store.devicePreview, 'tablet');

                    store.setDevicePreview('mobile');
                    self.assertEqual(store.devicePreview, 'mobile');

                    store.setDevicePreview('desktop');
                    self.assertEqual(store.devicePreview, 'desktop');
                });

                self.it('togglePanel debe alternar paneles', function() {
                    var wasPanelOpen = store.panels.blocks;

                    store.togglePanel('blocks');
                    self.assertEqual(store.panels.blocks, !wasPanelOpen);

                    store.togglePanel('blocks');
                    self.assertEqual(store.panels.blocks, wasPanelOpen);
                });

                self.it('isDirty debe indicar cambios sin guardar', function() {
                    store.isDirty = false;
                    store.addElement({ id: 'dirty_test', type: 'text' });

                    self.assertTrue(store.isDirty);
                });
            });
        }
    };

    // Ejecutar tests cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que Alpine esté listo
            setTimeout(function() {
                TestRunner.run();
            }, 500);
        });
    } else {
        setTimeout(function() {
            TestRunner.run();
        }, 500);
    }

    // Exponer para ejecución manual
    window.VBPTestRunner = TestRunner;

})();
