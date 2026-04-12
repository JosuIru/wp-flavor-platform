# VBP Stress Tests

Suite completa de pruebas de estres, fiabilidad y limites para Visual Builder Pro.

## Estructura

```
tests/stress/
├── stress-tests.js         # Tests de carga y rendimiento
├── plugin-compatibility.js # Tests de compatibilidad con plugins
├── data-consistency.js     # Tests de integridad de datos
├── error-recovery.js       # Tests de recuperacion de errores
├── limits-test.js          # Tests de limites del sistema
├── run-stress-tests.js     # Runner principal
├── index.js                # Entry point del modulo
├── RELIABILITY-REPORT.md   # Reporte de fiabilidad
└── README.md               # Esta documentacion
```

## Instalacion

```bash
cd /path/to/flavor-platform/tests/stress
npm install  # Si hay dependencias
```

## Uso

### Ejecutar Todos los Tests

```bash
node run-stress-tests.js
```

### Modo Rapido (iteraciones reducidas)

```bash
node run-stress-tests.js --quick
# o
node run-stress-tests.js -q
```

### Con Salida Detallada

```bash
node run-stress-tests.js --verbose
# o
node run-stress-tests.js -v
```

### Categoria Especifica

```bash
# Solo stress tests
node run-stress-tests.js --category stress

# Solo compatibilidad
node run-stress-tests.js --category compatibility

# Solo consistencia de datos
node run-stress-tests.js --category consistency

# Solo recuperacion de errores
node run-stress-tests.js --category recovery

# Solo limites del sistema
node run-stress-tests.js --category limits
```

### Test Individual

```bash
node run-stress-tests.js --category stress --test massive-elements
node run-stress-tests.js -c stress -t rapid-operations
```

## Categorias de Tests

### 1. Stress Tests (`stress-tests.js`)

Tests de rendimiento bajo carga:

| Test ID | Descripcion |
|---------|-------------|
| `massive-elements` | 1000+ elementos en canvas |
| `rapid-operations` | 100 operaciones rapidas |
| `concurrent-users` | 10 usuarios simultaneos |
| `save-stress` | Guardado durante edicion intensa |
| `crash-recovery` | Recuperacion de crash |
| `deep-nesting` | Anidamiento profundo |
| `memory-leak` | Deteccion de fugas de memoria |
| `large-payload` | Payload de datos grande |

### 2. Plugin Compatibility (`plugin-compatibility.js`)

Compatibilidad con 30+ plugins populares:

- **E-commerce**: WooCommerce
- **SEO**: Yoast, All in One SEO, Rank Math
- **Forms**: Contact Form 7, WPForms, Forminator
- **Security**: Wordfence, Sucuri, iThemes Security
- **Cache**: W3 Total Cache, WP Super Cache, LiteSpeed, WP Rocket
- **Page Builders**: Elementor, Beaver Builder, Divi
- **Multilingual**: WPML, Polylang, TranslatePress
- **Custom Fields**: ACF, Meta Box
- **Y mas...**

### 3. Data Consistency (`data-consistency.js`)

Tests de integridad de datos:

| Test ID | Descripcion |
|---------|-------------|
| `save-load-consistency` | Guardar y cargar produce datos identicos |
| `undo-redo-consistency` | Undo/Redo mantiene estados correctos |
| `symbol-sync` | Symbols se sincronizan a instancias |
| `snapshot-integrity` | Snapshots preservan estado completo |
| `concurrent-operations` | Operaciones paralelas no corrompen |
| `reference-integrity` | Referencias se mantienen validas |

### 4. Error Recovery (`error-recovery.js`)

Tests de recuperacion:

| Test ID | Descripcion |
|---------|-------------|
| `save-failure-recovery` | Recuperacion de guardado fallido |
| `version-conflict-recovery` | Manejo de conflictos de version |
| `session-expired-recovery` | Recuperacion de sesion expirada |
| `corrupt-data-recovery` | Deteccion y recuperacion de datos corruptos |
| `browser-crash-recovery` | Recuperacion de crash del navegador |
| `validation-error-recovery` | Manejo de errores de validacion |
| `exponential-backoff-retry` | Reintentos con backoff exponencial |

### 5. System Limits (`limits-test.js`)

Tests para encontrar limites:

| Test ID | Descripcion |
|---------|-------------|
| `max-elements` | Maximo numero de elementos |
| `max-nesting` | Maximo nivel de anidamiento |
| `max-symbols` | Maximo numero de symbols |
| `max-history` | Maximo tamano de historial |
| `max-page-size` | Maximo tamano de pagina |
| `max-operations-per-second` | Throughput maximo |
| `max-selection-size` | Maximo elementos seleccionados |

## Uso Programatico

### Node.js

```javascript
const vbpStress = require('./tests/stress');

// Ejecutar todos los tests
const results = await vbpStress.run();

// Modo rapido
const quickResults = await vbpStress.runQuick();

// Con verbose
const verboseResults = await vbpStress.runVerbose();

// Obtener tests disponibles
const availableTests = vbpStress.getAvailableTests();

// Informacion de un test
const testInfo = vbpStress.getTestInfo('stress', 'massive-elements');
```

### Navegador

```html
<script src="tests/stress/stress-tests.js"></script>
<script src="tests/stress/plugin-compatibility.js"></script>
<script src="tests/stress/data-consistency.js"></script>
<script src="tests/stress/error-recovery.js"></script>
<script src="tests/stress/limits-test.js"></script>
<script src="tests/stress/run-stress-tests.js"></script>

<script>
// Ejecutar tests
VBPStressTestSuite.runAllStressTests({ verbose: true })
    .then(results => console.log(results));

// O usar suites individuales
const stressRunner = new VBPStressTests.StressTestRunner({ verbose: true });
stressRunner.runAll().then(console.log);
</script>
```

## Configuracion

### Opciones Globales

```javascript
const options = {
    verbose: false,        // Salida detallada
    stopOnFailure: false,  // Parar en primer fallo
    quickMode: false,      // Modo rapido
    generateReport: true,  // Generar reporte
    reportFormat: 'markdown'  // Formato del reporte
};
```

### Opciones por Test

Cada test acepta opciones especificas:

```javascript
// Stress test con opciones
await STRESS_TESTS['massive-elements'].run({
    elementCount: 500,  // Numero de elementos
    batchSize: 50       // Tamano del batch
});

// Limits test con opciones
await LIMITS_TESTS['max-elements'].run({
    targetFPS: 30,      // FPS objetivo
    maxIterations: 2000 // Max iteraciones
});
```

## Interpretacion de Resultados

### Estados de Test

- **Pass**: Test exitoso, metricas dentro de limites
- **Fail**: Test fallido o metricas fuera de limites

### Metricas Clave

| Metrica | Umbral Aceptable |
|---------|-----------------|
| FPS | > 30 |
| Ops/segundo | > 50 |
| Tiempo guardado (1MB) | < 1000ms |
| Sync symbol | < 100ms |
| Memory growth | < 10MB |

### Reporte de Fiabilidad

Despues de ejecutar los tests, consultar:

```
tests/stress/RELIABILITY-REPORT.md
```

## Agregar Nuevos Tests

### Test de Stress

```javascript
// En stress-tests.js
STRESS_TESTS['my-new-test'] = {
    name: 'Mi Nuevo Test',
    description: 'Descripcion del test',
    category: 'performance',
    timeout: 30000,

    async run(options = {}) {
        // Implementacion
        const store = new MockVBPStore();

        // ... logica del test

        return {
            passed: true,
            metrics: { /* metricas */ },
            message: 'Resultado del test'
        };
    }
};
```

### Test de Consistencia

```javascript
// En data-consistency.js
DATA_CONSISTENCY_TESTS['my-consistency-test'] = {
    name: 'Mi Test de Consistencia',
    description: 'Verificar X',
    category: 'data',

    async run() {
        const store = new ConsistencyMockStore();

        // ... verificaciones

        return {
            passed: true,
            metrics: {},
            message: 'OK'
        };
    }
};
```

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/stress-tests.yml
name: Stress Tests

on:
  push:
    branches: [main, develop]
  pull_request:

jobs:
  stress-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Run Stress Tests
        run: |
          cd tests/stress
          node run-stress-tests.js --quick

      - name: Upload Report
        uses: actions/upload-artifact@v4
        with:
          name: reliability-report
          path: tests/stress/RELIABILITY-REPORT.md
```

## Troubleshooting

### Tests Lentos

Usar modo rapido:
```bash
node run-stress-tests.js --quick
```

### Errores de Memoria

Aumentar memoria de Node:
```bash
node --max-old-space-size=4096 run-stress-tests.js
```

### Tests Fallan en CI

- Verificar que los mocks estan correctamente configurados
- Reducir iteraciones para entorno CI
- Verificar timeouts

## Contribuir

1. Crear branch feature
2. Agregar tests siguiendo estructura existente
3. Documentar nuevos tests
4. Actualizar RELIABILITY-REPORT.md si es necesario
5. Crear PR

## Licencia

Parte del proyecto Flavor Platform. Ver LICENSE en raiz del proyecto.
