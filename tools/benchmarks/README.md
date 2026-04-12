# VBP Benchmark Suite

Suite de benchmarks para medir y comparar el rendimiento de Visual Builder Pro contra otros page builders.

## Instalacion

Los benchmarks estan incluidos en el directorio `tools/benchmarks/`. No requieren instalacion adicional para uso basico.

Para benchmarks automatizados con Playwright:

```bash
# Desde la raiz del plugin
npm install @playwright/test
npx playwright install chromium
```

## Uso Rapido

### CLI Interactivo

```bash
./tools/benchmarks/run-benchmarks.sh
```

Esto abre un menu interactivo para seleccionar y ejecutar benchmarks.

### Linea de Comandos

```bash
# Listar benchmarks disponibles
node tools/benchmarks/index.js list

# Ver info de un benchmark
node tools/benchmarks/index.js info landing-simple

# Comparar tiempo con competidores
node tools/benchmarks/index.js compare landing-simple 95

# Generar reportes
node tools/benchmarks/index.js report

# Ejecutar todos automatizados
./tools/benchmarks/run-benchmarks.sh --all --automated
```

## Benchmarks Disponibles

| ID | Nombre | Descripcion | Tiempo Objetivo |
|----|--------|-------------|-----------------|
| `landing-simple` | Landing Simple | Hero + 3 features + CTA | 120s |
| `home-corporate` | Home Corporativa | 8 secciones completas | 300s |
| `page-complex` | Pagina Compleja | Animaciones, simbolos, responsive | 480s |
| `blog-article` | Articulo Blog | Contenido + sidebar | 200s |
| `ecommerce-product` | Pagina Producto | Galeria + tabs + reviews | 240s |
| `portfolio-gallery` | Portfolio | Grid masonry + lightbox | 280s |

## Modos de Ejecucion

### 1. Manual (Navegador)

El panel de benchmarks se inyecta en el editor VBP:

1. Abre el editor VBP
2. Presiona `Alt+B` para mostrar el panel
3. Selecciona un benchmark
4. Haz clic en "Iniciar"
5. Completa los pasos manualmente
6. Marca cada paso como completado (clic o `Alt+S`)
7. Haz clic en "Finalizar"

### 2. Automatizado (Playwright)

Ejecuta benchmarks sin intervencion humana:

```bash
# Todos los benchmarks
npx playwright test tools/benchmarks/benchmark-automated.js

# Un benchmark especifico
npx playwright test tools/benchmarks/benchmark-automated.js --grep "landing-simple"

# Con UI visual
npx playwright test tools/benchmarks/benchmark-automated.js --headed
```

### 3. Rapido (Info)

Ver informacion y metricas esperadas sin ejecutar:

```bash
node tools/benchmarks/index.js info landing-simple
```

## Metricas Medidas

- **Tiempo total**: Segundos desde inicio hasta guardar
- **Clicks**: Numero de interacciones con mouse
- **Teclas**: Numero de pulsaciones de teclado
- **Errores**: Acciones fallidas o deshacer

## Sistema de Puntuacion

Cada metrica se evalua contra umbrales esperados:

- **100 puntos**: Mejor que el minimo esperado
- **75 puntos**: En el objetivo
- **40 puntos**: En el maximo aceptable
- **0-40 puntos**: Excede el maximo

La puntuacion final es ponderada:
- Tiempo: 40%
- Clicks: 30%
- Teclas: 20%
- Errores: 10%

### Ratings

| Score | Rating | Descripcion |
|-------|--------|-------------|
| 90+ | Excelente | Rendimiento optimo |
| 75-89 | Bueno | Por encima del objetivo |
| 60-74 | Aceptable | Dentro de parametros |
| 40-59 | Mejorable | Necesita practica |
| 0-39 | Deficiente | Revisar flujo de trabajo |

## Competidores Comparados

| Herramienta | Categoria |
|-------------|-----------|
| Elementor Pro | Page Builder |
| Gutenberg + Patterns | Nativo WP |
| Divi Builder | Page Builder |
| Webflow | SaaS Externo |
| Codigo Manual | Desarrollo |

Los tiempos de competidores son baselines de la industria.

## Curva de Aprendizaje

El sistema ajusta expectativas segun experiencia:

| Nivel | Multiplicador | Descripcion |
|-------|---------------|-------------|
| Primera vez | 2.5x | Nuevo en VBP |
| Principiante | 1.8x | <5 paginas |
| Intermedio | 1.2x | 5-20 paginas |
| Experimentado | 1.0x | 20-50 paginas |
| Experto | 0.8x | >50 paginas |

## Reportes

Los reportes se guardan en `reports/benchmarks/`:

- `BENCHMARK-RESULTS.md` - Reporte principal en Markdown
- `benchmark-report-*.html` - Reporte visual HTML
- `benchmark-report-*.json` - Datos estructurados
- `benchmark-results-*.json` - Resultados crudos

### Generar Reportes

```bash
# Desde CLI
node tools/benchmarks/index.js report

# O desde el script
./tools/benchmarks/run-benchmarks.sh --report
```

## Estructura de Archivos

```
tools/benchmarks/
├── index.js              # Entry point principal
├── benchmark-config.js   # Configuracion y definiciones
├── benchmark-runner.js   # Motor de ejecucion
├── benchmark-ui.js       # Panel UI para navegador
├── benchmark-automated.js # Tests Playwright
├── benchmark-report.js   # Generador de reportes
├── run-benchmarks.sh     # CLI interactivo
└── README.md             # Esta documentacion
```

## API JavaScript

### Node.js

```javascript
const { BenchmarkRunner, BENCHMARKS, COMPETITORS } = require('./tools/benchmarks');

// Crear runner
const runner = new BenchmarkRunner({ verbose: true });

// Obtener info
const benchmarks = runner.getAvailableBenchmarks();

// Simular ejecucion
runner.start('landing-simple', { experienceLevel: 'intermediate' });
runner.stepComplete('add-hero');
runner.stepComplete('edit-title');
// ... mas pasos
const report = runner.finish();
console.log(report);
```

### Navegador

```javascript
// Disponible globalmente cuando se carga en VBP
const { runner, ui } = window.VBPBenchmark.init();

// O manualmente
const runner = new window.VBPBenchmarkRunner();
const ui = new window.VBPBenchmarkUI(runner);
ui.show();
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Alt+B` | Mostrar/ocultar panel |
| `Alt+S` | Marcar paso actual como completado |
| `Alt+F` | Finalizar benchmark |

## Integracion con CI/CD

```yaml
# GitHub Actions example
- name: Run VBP Benchmarks
  run: |
    npx playwright test tools/benchmarks/benchmark-automated.js
  env:
    WP_BASE_URL: ${{ secrets.WP_BASE_URL }}
    WP_ADMIN_USER: ${{ secrets.WP_ADMIN_USER }}
    WP_ADMIN_PASS: ${{ secrets.WP_ADMIN_PASS }}
```

## Contribuir

Para agregar nuevos benchmarks:

1. Agregar definicion en `benchmark-config.js`
2. Agregar pasos automatizados en `benchmark-automated.js`
3. Documentar en este README

## Licencia

Parte de Flavor Platform. Ver licencia principal del plugin.
