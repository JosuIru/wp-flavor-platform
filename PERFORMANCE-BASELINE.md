# VBP Performance Baseline

> Version: 1.0.0
> Ultima actualizacion: Pendiente de ejecucion de tests

Este documento define los umbrales de rendimiento objetivo para Visual Builder Pro y sirve como referencia para detectar regresiones.

## Metricas del Editor

### Tiempo de Carga

| Metrica | Bueno | Aceptable | Pobre | Descripcion |
|---------|-------|-----------|-------|-------------|
| Tiempo de carga | <= 2s | <= 4s | > 6s | Tiempo desde navegacion hasta DOMContentLoaded |
| TTI | <= 3s | <= 5s | > 8s | Time to Interactive |
| Memoria inicial | <= 50MB | <= 100MB | > 200MB | Uso de memoria JS heap al cargar |
| Memoria pico | <= 100MB | <= 200MB | > 400MB | Uso maximo de memoria |

### Rendimiento en Operacion

| Metrica | Bueno | Aceptable | Pobre | Descripcion |
|---------|-------|-----------|-------|-------------|
| FPS (drag) | >= 55 | >= 45 | < 30 | Frames por segundo durante arrastrar |
| FPS (idle) | >= 58 | >= 50 | < 40 | Frames por segundo sin actividad |
| Render time | <= 16ms | <= 33ms | > 100ms | Tiempo promedio de render de frame |
| Save time | <= 500ms | <= 1.5s | > 3s | Tiempo para guardar cambios |

## Metricas de Pagina Generada (Core Web Vitals)

### Vitales Principales

| Metrica | Bueno | Aceptable | Pobre | Descripcion |
|---------|-------|-----------|-------|-------------|
| TTFB | <= 200ms | <= 500ms | > 1s | Time to First Byte |
| FCP | <= 1.8s | <= 3s | > 5s | First Contentful Paint |
| LCP | <= 2.5s | <= 4s | > 6s | Largest Contentful Paint |
| CLS | <= 0.1 | <= 0.25 | > 0.5 | Cumulative Layout Shift |
| FID | <= 100ms | <= 300ms | > 500ms | First Input Delay |
| INP | <= 200ms | <= 500ms | > 1s | Interaction to Next Paint |

### Tamano y Complejidad

| Metrica | Bueno | Aceptable | Pobre | Descripcion |
|---------|-------|-----------|-------|-------------|
| DOM Size | <= 800 | <= 1500 | > 3000 | Numero total de nodos DOM |
| DOM Depth | <= 15 | <= 25 | > 40 | Maxima profundidad de anidamiento |
| CSS Size | <= 50KB | <= 100KB | > 200KB | Tamano total de CSS |
| JS Size | <= 100KB | <= 200KB | > 400KB | Tamano total de JS |
| Image Size | <= 500KB | <= 1MB | > 2MB | Tamano total de imagenes |
| Total Requests | <= 30 | <= 60 | > 100 | Numero de peticiones HTTP |

## Escalabilidad

### Rendimiento por Cantidad de Elementos

| Elementos | Carga | Render | FPS | Memoria |
|-----------|-------|--------|-----|---------|
| 10 | <= 50ms | <= 16ms | ~60 | ~50MB |
| 50 | <= 150ms | <= 50ms | >= 55 | ~80MB |
| 100 | <= 300ms | <= 100ms | >= 50 | ~100MB |
| 500 | <= 1000ms | <= 300ms | >= 40 | ~200MB |

### Factores de Escalabilidad Esperados

- **Lineal** (ideal): Tiempo crece 1:1 con elementos
- **Sub-cuadratico** (aceptable): Tiempo crece < 1.5x por duplicacion
- **Cuadratico** (problematico): Tiempo crece > 2x por duplicacion

## Como Ejecutar Tests

### Test Completo

```bash
# Ejecutar todos los tests de rendimiento
bash tools/run-performance-tests.sh "http://tu-sitio.local"

# Solo Lighthouse CI
npx lhci autorun
```

### Test de Editor (Browser)

```javascript
// En la consola del navegador con el editor abierto

// Iniciar recolector de metricas
const editorMetrics = new VBPEditorMetrics.EditorMetricsCollector({ debug: true });
editorMetrics.start();

// Interactuar con el editor...

// Obtener metricas
console.log(editorMetrics.exportMetrics());
```

### Test de Escalabilidad (Browser)

```javascript
// En la consola del navegador
const test = new VBPScalabilityTest.ScalabilityTest({
    elementCounts: [10, 50, 100, 500],
    testRuns: 3,
    debug: true
});

const report = await test.run();
console.log(test.toMarkdown());
```

### Test de Output (Browser)

```javascript
// En la consola del navegador de una pagina VBP
const outputMetrics = new VBPOutputMetrics.OutputMetricsCollector({ debug: true });
outputMetrics.start();

// Esperar carga completa
setTimeout(() => {
    console.log(outputMetrics.exportMetrics());
}, 5000);
```

## Dashboard de Performance

Abrir `tools/performance-dashboard.html` en un navegador para:

- Visualizar metricas con graficos
- Comparar con baselines
- Ver historico de mediciones
- Detectar regresiones

## Integracion CI/CD

### GitHub Actions

```yaml
- name: Run Performance Tests
  run: |
    npm install -g @lhci/cli
    bash tools/run-performance-tests.sh "${{ env.SITE_URL }}"

- name: Check Performance Regression
  run: npx lhci autorun --assert
```

### Umbrales de CI

El archivo `.lighthouserc.js` define los umbrales que causan fallo en CI:

- Performance Score < 90%: Error
- LCP > 2.5s: Error
- CLS > 0.1: Error
- TBT > 300ms: Error

## Recomendaciones de Optimizacion

### Alta Prioridad

1. **Mantener LCP < 2.5s**
   - Preload de imagen hero
   - Optimizar ruta critica de renderizado
   - Usar CDN para assets

2. **CLS < 0.1**
   - Definir dimensiones de imagenes
   - Reservar espacio para contenido dinamico
   - Evitar insercion de contenido sobre existente

3. **FPS > 50 durante drag**
   - Usar requestAnimationFrame
   - Throttle de event handlers
   - Evitar forced reflows

### Media Prioridad

1. **Reducir bundle JS**
   - Code splitting por modulo
   - Lazy loading de componentes
   - Tree shaking

2. **Optimizar CSS**
   - Purgar estilos no usados
   - Critical CSS inline
   - Defer estilos no criticos

3. **Virtualizacion**
   - Implementar para listas > 50 elementos
   - Usar windowing para grandes datasets

### Mejoras Continuas

1. Ejecutar tests en cada PR
2. Configurar alertas para regresiones > 10%
3. Revisar metricas semanalmente
4. Documentar optimizaciones aplicadas

## Historico de Mediciones

| Fecha | Score | LCP | CLS | FPS Drag | Notas |
|-------|-------|-----|-----|----------|-------|
| - | - | - | - | - | Pendiente de medicion inicial |

---

*Este baseline se actualiza automaticamente al ejecutar `bash tools/run-performance-tests.sh`*
