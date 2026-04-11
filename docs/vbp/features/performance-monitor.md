# Performance Monitor

Panel de monitoreo de rendimiento en tiempo real para el editor VBP.

## Descripcion

El Performance Monitor permite visualizar metricas de rendimiento del editor en tiempo real, identificar cuellos de botella, y optimizar tanto el editor como el contenido creado.

## Como Acceder

- Atajo: `Ctrl+Shift+P` > "Performance"
- Menu: View > Performance Monitor
- DevTools: Panel VBP

## Metricas Monitoreadas

### Editor Performance

| Metrica | Descripcion | Objetivo |
|---------|-------------|----------|
| FPS | Frames por segundo | > 30 |
| Memory | Uso de memoria | < 500MB |
| DOM Nodes | Elementos en DOM | < 5000 |
| Layout Thrashing | Reflows/sec | < 5 |
| Paint Time | Tiempo de pintura | < 16ms |
| Script Time | Tiempo de script | < 10ms |

### Canvas Performance

| Metrica | Descripcion | Objetivo |
|---------|-------------|----------|
| Render Time | Tiempo de render | < 16ms |
| Elements | Elementos en canvas | < 500 |
| Visible | Elementos visibles | < 100 |
| Updates/sec | Actualizaciones | < 60 |

### Network

| Metrica | Descripcion | Objetivo |
|---------|-------------|----------|
| API Calls | Requests activas | < 5 |
| Save Time | Tiempo de guardado | < 2s |
| Load Time | Tiempo de carga | < 3s |
| Payload Size | Tamano de datos | < 1MB |

## Panel de Performance

### Vista General

```
PERFORMANCE MONITOR
┌─────────────────────────────────────────┐
│  FPS: 58 [████████████████████░░] 97%   │
│  MEM: 245MB [███████░░░░░░░░░░░░] 49%   │
│  DOM: 2341 [█████░░░░░░░░░░░░░░░] 47%   │
└─────────────────────────────────────────┘

TIMELINE
[===========|===|==|====|=============|===]
              ↑        ↑
           Paint    Script

ISSUES (2)
• Layout thrashing detected
• Large DOM tree
```

### Timeline

Visualizacion en tiempo real de:
- Frame timing
- Script execution
- Layout/Paint
- Idle time

### Problemas Detectados

El monitor identifica automaticamente:

| Problema | Causa | Solucion |
|----------|-------|----------|
| Low FPS | Demasiados elementos | Reducir complejidad |
| Memory Leak | Referencias no liberadas | Limpiar listeners |
| Layout Thrashing | Reads/writes mezclados | Batch operations |
| Long Tasks | Scripts bloqueantes | Async/Workers |
| Large DOM | Muchos nodos | Virtual scrolling |

## API JavaScript

### VBPPerformanceMonitor

```javascript
const perf = window.VBPPerformanceMonitor;

// Estado
perf.start();                    // Iniciar monitoreo
perf.stop();                     // Detener monitoreo
perf.isRunning();                // Estado del monitor

// Metricas
perf.getFPS();                   // FPS actual
perf.getMemory();                // Uso de memoria
perf.getDOMCount();              // Nodos DOM
perf.getMetrics();               // Todas las metricas

// Historial
perf.getHistory(minutes);        // Historial de metricas
perf.clearHistory();             // Limpiar historial

// Marcadores
perf.mark('myOperation:start');  // Crear marca
perf.mark('myOperation:end');
perf.measure('myOperation', 'myOperation:start', 'myOperation:end');
perf.getMeasures();              // Obtener mediciones

// Profiling
perf.startProfiling();           // Iniciar profiling
perf.stopProfiling();            // Detener y obtener datos

// Reportes
perf.generateReport();           // Generar reporte
perf.exportReport(format);       // Exportar ('json', 'csv')
```

### Metricas Detalladas

```javascript
const metrics = perf.getMetrics();

// Estructura de metricas
{
    fps: {
        current: 58,
        average: 55,
        min: 30,
        max: 60
    },
    memory: {
        used: 245 * 1024 * 1024,   // bytes
        total: 512 * 1024 * 1024,
        percentage: 48
    },
    dom: {
        total: 2341,
        canvas: 456,
        panels: 1885
    },
    timing: {
        script: 8,                 // ms
        render: 12,
        paint: 3,
        layout: 2,
        idle: 75
    },
    network: {
        activeRequests: 2,
        pendingRequests: 0,
        lastSaveTime: 1250,        // ms
        lastLoadTime: 2100
    }
}
```

### Observadores

```javascript
// Observar metricas
perf.observe('fps', (value) => {
    if (value < 30) {
        console.warn('FPS bajo:', value);
    }
});

// Observar umbral
perf.observeThreshold('memory', 400 * 1024 * 1024, () => {
    console.warn('Memoria alta, limpiando cache...');
    // Accion de limpieza
});

// Remover observador
perf.unobserve('fps', callback);
```

## Eventos

```javascript
// FPS bajo
document.addEventListener('vbp:perf:fps:low', (e) => {
    console.warn('FPS bajo:', e.detail.fps);
});

// Memoria alta
document.addEventListener('vbp:perf:memory:high', (e) => {
    console.warn('Memoria alta:', e.detail.used);
});

// Long task detectada
document.addEventListener('vbp:perf:longtask', (e) => {
    console.warn('Long task:', e.detail.duration, 'ms');
});

// Problema detectado
document.addEventListener('vbp:perf:issue', (e) => {
    console.log('Problema:', e.detail.type);
    console.log('Sugerencia:', e.detail.suggestion);
});
```

## Optimizaciones Automaticas

VBP aplica optimizaciones automaticas cuando detecta problemas:

### Adaptive Quality

```javascript
// Reducir calidad cuando FPS es bajo
if (fps < 30) {
    // Reduce sombras
    // Reduce animaciones
    // Activa virtual scrolling
}
```

### Memory Management

```javascript
// Liberar memoria cuando es alta
if (memoryUsage > 70%) {
    // Limpia cache de imagenes
    // Libera elementos fuera del viewport
    // Fuerza garbage collection hint
}
```

### Throttling

```javascript
// Reduce frecuencia de operaciones costosas
if (fps < 45) {
    // Aumenta debounce de inputs
    // Reduce frecuencia de re-renders
    // Postpone operaciones no criticas
}
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Shift+F` | Toggle Performance Monitor |
| `Ctrl+Shift+M` | Tomar snapshot de memoria |
| `Ctrl+Alt+T` | Iniciar/Detener timeline |

## Configuracion

```javascript
perf.configure({
    // Intervalos
    sampleInterval: 100,         // ms entre muestras
    historyDuration: 60000,      // 1 minuto de historial

    // Umbrales
    thresholds: {
        fpsLow: 30,
        memoryHigh: 0.7,         // 70%
        domHigh: 5000,
        longTask: 50             // ms
    },

    // Features
    autoOptimize: true,
    showOverlay: true,
    logToConsole: false
});
```

## Herramientas de Diagnostico

### Profiler

```javascript
// Perfilar operacion
perf.startProfiling();

// ... operacion costosa ...

const profile = perf.stopProfiling();
console.log(profile.summary);
```

### Heap Snapshot

```javascript
// Tomar snapshot de memoria
const snapshot = await perf.takeHeapSnapshot();

// Comparar snapshots
const diff = perf.compareSnapshots(snapshot1, snapshot2);
console.log('Objetos nuevos:', diff.added);
console.log('Objetos liberados:', diff.removed);
```

### Flame Graph

```javascript
// Generar flame graph
const flame = perf.generateFlameGraph();
perf.showFlameGraph(flame);
```

## Integracion con DevTools

El Performance Monitor se integra con DevTools del navegador:

1. Abre DevTools (F12)
2. Ve a la pestaña "VBP"
3. Selecciona "Performance"

### Console API

```javascript
// En la consola del navegador
VBPPerf.fps()          // FPS actual
VBPPerf.memory()       // Memoria actual
VBPPerf.report()       // Reporte completo
VBPPerf.profile(fn)    // Perfilar funcion
```

## Recomendaciones

### Para Mejorar FPS

1. Reduce elementos visibles simultaneamente
2. Usa virtual scrolling para listas largas
3. Evita animaciones complejas
4. Optimiza imagenes

### Para Reducir Memoria

1. Limpia listeners no usados
2. Libera referencias a elementos eliminados
3. Usa lazy loading para assets
4. Evita copias innecesarias de datos

### Para Evitar Layout Thrashing

```javascript
// MAL
elements.forEach(el => {
    el.style.width = (el.offsetWidth + 10) + 'px';  // Lee y escribe
});

// BIEN
const widths = elements.map(el => el.offsetWidth);  // Lee todo
elements.forEach((el, i) => {
    el.style.width = (widths[i] + 10) + 'px';       // Escribe todo
});
```

## Solucionar Problemas

### FPS constantemente bajo

1. Reduce complejidad del documento
2. Desactiva animaciones
3. Verifica extensiones del navegador
4. Actualiza drivers de video

### Memoria sigue creciendo

1. Busca memory leaks con DevTools
2. Verifica listeners no removidos
3. Comprueba closures que retienen referencias
4. Reinicia el editor periodicamente

### El monitor afecta el rendimiento

1. Reduce frecuencia de muestreo
2. Desactiva historial
3. Usa modo minimal
4. Desactiva cuando no sea necesario
