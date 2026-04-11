# Tests - Visual Builder Pro

Suite completa de tests automatizados para Visual Builder Pro.

## Estructura

```
tests/
├── php/                           # Tests PHPUnit
│   ├── test-class-vbp-symbols.php      # Tests de Symbols
│   ├── test-class-vbp-branching.php    # Tests de Branching
│   ├── test-class-vbp-rest-api.php     # Tests de REST API
│   └── test-class-vbp-global-styles.php # Tests de Global Styles
│
├── js/                            # Tests JavaScript (Jest/Vitest)
│   ├── setup.js                        # Configuracion de mocks
│   ├── __mocks__/                      # Mocks de modulos
│   ├── vbp-store.test.js               # Tests del Store
│   ├── vbp-symbols.test.js             # Tests de Symbols
│   ├── vbp-responsive.test.js          # Tests de Responsive
│   ├── vbp-constraints.test.js         # Tests de Constraints
│   └── vbp-animation-builder.test.js   # Tests de Animaciones
│
├── e2e/                           # Tests E2E (Playwright)
│   ├── auth.setup.js                   # Autenticacion
│   ├── editor.spec.js                  # Tests del Editor
│   ├── symbols.spec.js                 # Tests de Symbols
│   ├── collaboration.spec.js           # Tests de Colaboracion
│   └── prototype.spec.js               # Tests de Prototype Mode
│
├── bootstrap.php                  # Bootstrap PHPUnit
├── class-vbp-unit-test-case.php   # Clase base para tests PHP
├── mock-wp-test-framework.php     # Framework mock WordPress
│
└── README.md                      # Esta documentacion
```

## Ejecutar Tests

### Todos los tests

```bash
# Tests PHP + JS
npm test

# Tests PHP + JS + E2E
npm run test:all
```

### Tests PHP (PHPUnit)

```bash
# Todos los tests PHP
npm run test:php

# Solo tests VBP
npm run test:php:vbp

# Con cobertura
npm run test:coverage:php

# Suite especifica
vendor/bin/phpunit --testsuite VBP-Symbols
vendor/bin/phpunit --testsuite VBP-Branching
vendor/bin/phpunit --testsuite VBP-API
vendor/bin/phpunit --testsuite VBP-GlobalStyles
```

### Tests JavaScript (Vitest)

```bash
# Ejecutar todos los tests JS
npm run test:js

# Modo watch (desarrollo)
npm run test:js:watch

# Con interfaz visual
npm run test:js:ui

# Con cobertura
npm run test:coverage:js

# Usando Jest (alternativo)
npm run test:js:jest
```

### Tests E2E (Playwright)

```bash
# Ejecutar todos los tests E2E
npm run test:e2e

# Con interfaz visual
npm run test:e2e:ui

# En modo headed (ver navegador)
npm run test:e2e:headed

# Solo Chromium
npm run test:e2e:chromium

# Solo Firefox
npm run test:e2e:firefox

# Solo WebKit/Safari
npm run test:e2e:webkit
```

### Variables de Entorno E2E

```bash
# URL base de WordPress
export WP_BASE_URL=http://sitio-prueba.local

# Credenciales admin
export WP_ADMIN_USER=admin
export WP_ADMIN_PASS=admin
```

## Tests Incluidos

### PHP (PHPUnit)

| Archivo | Descripcion |
|---------|-------------|
| `test-class-vbp-symbols.php` | Creacion, instancias, variantes, import/export |
| `test-class-vbp-branching.php` | Ramas, checkout, merge, conflictos, historial |
| `test-class-vbp-rest-api.php` | Autenticacion API, rate limiting, endpoints |
| `test-class-vbp-global-styles.php` | Estilos globales, categorias, CSS generation |

### JavaScript (Vitest)

| Archivo | Descripcion |
|---------|-------------|
| `vbp-store.test.js` | Store Alpine, elementos, seleccion, historial |
| `vbp-symbols.test.js` | Symbols, instancias, variantes, overrides |
| `vbp-responsive.test.js` | Breakpoints, responsive overrides, CSS |
| `vbp-constraints.test.js` | Constraints horizontales/verticales, aspect ratio |
| `vbp-animation-builder.test.js` | Keyframes, triggers, easing, timeline |

### E2E (Playwright)

| Archivo | Descripcion |
|---------|-------------|
| `editor.spec.js` | Cargar editor, agregar elementos, guardar |
| `symbols.spec.js` | Crear symbols, insertar instancias |
| `collaboration.spec.js` | Comentarios, historial de versiones |
| `prototype.spec.js` | Modo prototipo, interacciones, animaciones |

## Agregar Nuevos Tests

### Test PHP

```php
<?php
/**
 * Tests for My Feature.
 */
class Test_My_Feature extends VBP_UnitTestCase {

    protected function setUp(): void {
        parent::setUp();
        // Setup code
    }

    public function test_feature_works() {
        // Arrange
        $input = 'test';

        // Act
        $result = my_function( $input );

        // Assert
        $this->assertEquals( 'expected', $result );
    }

    public function test_feature_handles_error() {
        $result = my_function( null );

        $this->assertInstanceOf( 'WP_Error', $result );
    }
}
```

### Test JavaScript

```javascript
/**
 * Tests for My Feature.
 */
describe('My Feature', () => {
    beforeEach(() => {
        // Setup code
    });

    test('feature works correctly', () => {
        // Arrange
        const input = 'test';

        // Act
        const result = myFunction(input);

        // Assert
        expect(result).toBe('expected');
    });

    test('handles errors gracefully', async () => {
        // Mock fetch error
        global.fetch.mockRejectedValueOnce(new Error('Network error'));

        // Test error handling
        await expect(myAsyncFunction()).rejects.toThrow('Network error');
    });
});
```

### Test E2E

```javascript
// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('My Feature', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor`);
        await page.waitForSelector('.vbp-editor');
    });

    test('feature is visible', async ({ page }) => {
        await expect(page.locator('.my-feature')).toBeVisible();
    });

    test('feature responds to interaction', async ({ page }) => {
        await page.locator('.my-feature-button').click();
        await expect(page.locator('.my-feature-result')).toHaveText('Success');
    });
});
```

## Convenciones de Naming

- **PHP**: `test-class-{nombre-clase}.php` con clase `Test_{Nombre_Clase}`
- **JS**: `{nombre-modulo}.test.js` con `describe('{Nombre Modulo}')`
- **E2E**: `{feature}.spec.js` con `test.describe('{Feature}')`

## Cobertura

| Componente | PHP | JS | E2E |
|------------|-----|----|----|
| Store | - | OK | OK |
| Symbols | OK | OK | OK |
| Branching | OK | - | - |
| Global Styles | OK | - | - |
| REST API | OK | - | - |
| Responsive | - | OK | - |
| Constraints | - | OK | - |
| Animations | - | OK | - |
| Collaboration | - | - | OK |
| Prototype Mode | - | - | OK |

## CI Integration

Los tests se ejecutan automaticamente en GitHub Actions:

```yaml
# .github/workflows/tests.yml
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install dependencies
        run: |
          composer install
          npm ci

      - name: Run PHP tests
        run: npm run test:php

      - name: Run JS tests
        run: npm run test:js

      - name: Install Playwright
        run: npx playwright install --with-deps

      - name: Run E2E tests
        run: npm run test:e2e
```

## Notas

- Los tests PHP pueden ejecutarse standalone con mocks o con WordPress test suite
- Los tests JS usan Vitest por defecto, Jest disponible como alternativa
- Los tests E2E requieren una instalacion WordPress accesible
- Los mocks de Alpine.js y WordPress estan en `tests/js/setup.js`
- La autenticacion E2E se maneja en `tests/e2e/auth.setup.js`
