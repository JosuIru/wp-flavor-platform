# Tests - Visual Builder Pro

Tests básicos para el editor visual VBP.

## Estructura

```
tests/
├── js/
│   ├── vbp-store.test.js    # Tests del store Alpine
│   └── test-runner.html     # Runner HTML para navegador
│
├── php/
│   └── test-vbp-rest-api.php  # Tests REST API
│
└── README.md
```

## Tests JavaScript

### Ejecutar en navegador

1. Abrir `tests/js/test-runner.html` en el navegador
2. Abrir la consola de desarrollo (F12)
3. Los tests se ejecutan automáticamente

### Tests incluidos

- **VBPPerformance Utilities**: debounce, throttle, memoize, deepClone
- **Store Initialization**: propiedades requeridas, initElements
- **Element Operations**: add, get, update, duplicate, remove, move
- **Selection Management**: select, multi-select, deselect, selectAll
- **History Operations**: undo, redo, pushHistory
- **UI State**: zoom, devicePreview, togglePanel, isDirty

### Ejecutar manualmente

```javascript
// En la consola del navegador
VBPTestRunner.run();
```

## Tests PHP

### Ejecutar con WP-CLI

```bash
wp eval-file wp-content/plugins/flavor-chat-ia/tests/php/test-vbp-rest-api.php
```

### Ejecutar desde navegador

Incluir el archivo en un contexto WordPress con acceso de administrador.

### Tests incluidos

- **REST API Registration**: namespace y rutas registradas
- **Document Operations**: guardar, cargar, estructura de elementos
- **Blocks Library**: existencia, categorización, propiedades
- **Element Rendering**: clase VBP_Canvas, renderizado básico
- **Data Validation**: sanitización HTML, validación de IDs
- **Permissions**: autenticación, permisos de administrador

## Agregar nuevos tests

### JavaScript

```javascript
// En vbp-store.test.js, dentro de TestRunner

testNuevoFeature: function() {
    var self = this;

    this.describe('Nuevo Feature', function() {
        self.it('debe hacer algo', function() {
            // Tu test aquí
            self.assertEqual(actual, expected);
        });
    });
}
```

### PHP

```php
// En test-vbp-rest-api.php

private function test_nuevo_feature() {
    $this->describe( 'Nuevo Feature' );

    $this->it(
        'debe hacer algo',
        function() {
            // Tu test aquí
            return $actual === $expected;
        }
    );
}
```

## Cobertura

| Componente | Cubierto |
|------------|----------|
| Store (Alpine) | ✅ |
| Performance Utils | ✅ |
| REST API | ✅ |
| Rendering | Básico |
| Validación | ✅ |
| Permisos | ✅ |

## Notas

- Los tests JavaScript requieren que Alpine.js esté cargado
- Los tests PHP requieren contexto WordPress completo
- Los tests crean y eliminan datos temporales automáticamente
