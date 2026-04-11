# Undo/Redo (Deshacer/Rehacer)

Sistema de historial que permite deshacer y rehacer cambios en el documento.

## Descripcion

El sistema de Undo/Redo mantiene un historial de todos los cambios realizados en el documento, permitiendo navegar hacia atras y adelante en el tiempo. Soporta agrupacion de cambios, snapshots y integracion con Version History.

## Como Usar

### Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Z` | Deshacer |
| `Ctrl+Shift+Z` | Rehacer |
| `Ctrl+Y` | Rehacer (alternativo) |

### Toolbar

Los botones de Undo/Redo estan en la toolbar superior, junto al logo.

## Funcionamiento

### Estados del Historial

```
PASADO                    PRESENTE                    FUTURO
[Estado 1] [Estado 2] [Estado 3]  ACTUAL  [Estado 5] [Estado 6]
    <------ Undo                              Redo ------>
```

### Acciones que se Registran

- Agregar elementos
- Eliminar elementos
- Modificar propiedades
- Mover elementos
- Duplicar elementos
- Pegar contenido
- Aplicar estilos globales
- Cambios de layout

### Acciones que NO se Registran

- Cambios de zoom
- Cambios de panel
- Seleccion de elementos
- Cambios de breakpoint (vista)

## Configuracion

### Limites de Historial

```php
// wp-config.php
define('VBP_UNDO_STACK_LIMIT', 100);  // Maximo de estados
```

```javascript
// JavaScript config
const historyConfig = {
    maxUndoStates: 100,
    debounceMs: 300,         // Agrupar cambios rapidos
    snapshotInterval: 5000   // Snapshot cada 5 segundos
};
```

### Agrupacion de Cambios

Cambios rapidos consecutivos se agrupan automaticamente:

```javascript
// Configurar debounce
Alpine.store('vbp').history.setDebounce(500); // 500ms

// Agrupar manualmente
const store = Alpine.store('vbp');

store.startBatch();
// Multiples cambios...
store.updateElement(id1, { color: 'red' });
store.updateElement(id2, { color: 'blue' });
store.updateElement(id3, { color: 'green' });
// Se registra como un solo undo
store.endBatch();
```

## API JavaScript

### Metodos del Store

```javascript
const store = Alpine.store('vbp');

// Deshacer/Rehacer
store.undo();                        // Deshacer ultimo cambio
store.redo();                        // Rehacer cambio deshecho

// Estado
store.canUndo();                     // true si hay algo que deshacer
store.canRedo();                     // true si hay algo que rehacer
store.getUndoCount();                // Numero de estados en pasado
store.getRedoCount();                // Numero de estados en futuro

// Historial
store.history.past;                  // Array de estados pasados
store.history.future;                // Array de estados futuros

// Batch (agrupar cambios)
store.startBatch();                  // Iniciar agrupacion
store.endBatch();                    // Finalizar agrupacion
store.cancelBatch();                 // Cancelar agrupacion

// Snapshots
store.pushHistory(snapshot);         // Guardar estado manualmente
store.clearHistory();                // Limpiar historial

// Navegacion
store.gotoHistoryState(index);       // Ir a estado especifico
store.getHistoryState(index);        // Obtener estado sin ir
```

### VBPHistory (Helpers)

```javascript
const history = window.VBPHistory;

// Obtener descripcion del ultimo cambio
history.getLastChangeDescription();  // "Cambio de color en Button"

// Obtener lista de cambios
history.getChangeList();
// [
//   { type: 'update', elementId: 'el_123', description: 'Cambio de color' },
//   { type: 'add', elementId: 'el_456', description: 'Nuevo Button' },
//   ...
// ]

// Comparar estados
history.diff(stateA, stateB);
// { added: [...], removed: [...], modified: [...] }
```

### Eventos

```javascript
// Historial modificado
document.addEventListener('vbp:history:changed', (e) => {
    console.log('Puede deshacer:', e.detail.canUndo);
    console.log('Puede rehacer:', e.detail.canRedo);
});

// Undo ejecutado
document.addEventListener('vbp:history:undo', (e) => {
    console.log('Cambio deshecho:', e.detail.description);
});

// Redo ejecutado
document.addEventListener('vbp:history:redo', (e) => {
    console.log('Cambio rehecho:', e.detail.description);
});

// Historial limpiado
document.addEventListener('vbp:history:cleared', () => {
    console.log('Historial limpiado');
});
```

## Integracion con Version History

El sistema de Undo/Redo trabaja junto con Version History:

### Diferencias

| Caracteristica | Undo/Redo | Version History |
|----------------|-----------|-----------------|
| Alcance | Sesion actual | Persistente |
| Granularidad | Por cambio | Por guardado |
| Almacenamiento | Memoria | Base de datos |
| Limite | ~100 estados | Configurable |

### Crear Punto de Restauracion

```javascript
// Guardar estado actual como version
const store = Alpine.store('vbp');
await store.saveDocument();  // Crea revision en WordPress

// O explicitamente
const versionHistory = window.VBPVersionHistory;
await versionHistory.createSnapshot('Antes de cambios grandes');
```

### Restaurar Version Anterior

```javascript
// Ver versiones
const versions = await versionHistory.getVersions(postId);

// Restaurar
await versionHistory.restore(postId, versionId);
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Z` | Deshacer |
| `Ctrl+Shift+Z` | Rehacer |
| `Ctrl+Y` | Rehacer (alternativo) |
| `Ctrl+Alt+D` | Comparar versiones |

## Consideraciones

### Performance

- El historial se mantiene en memoria
- Estados muy grandes pueden consumir RAM
- Se recomienda guardar frecuentemente

### Colaboracion

- El Undo/Redo es local (por usuario)
- No afecta los cambios de otros usuarios
- Los bloqueos se respetan

### Limpieza

El historial se limpia cuando:
- Se recarga la pagina
- Se cambia de documento
- Se llama `clearHistory()`
- Se alcanza el limite

## Solucionar Problemas

### Undo no funciona

1. Verifica que hay cambios que deshacer
2. Comprueba que el limite no se alcanzo
3. Revisa que no estas en modo de edicion inline

### Se perdieron cambios

1. El historial es por sesion, se pierde al recargar
2. Guarda frecuentemente (Ctrl+S)
3. Usa Version History para recuperar guardados

### Performance lenta

1. Reduce el limite de historial
2. Evita cambios muy frecuentes sin debounce
3. Considera limpiar historial periodicamente

### Redo no disponible

El Redo solo funciona inmediatamente despues de un Undo. Si haces un nuevo cambio, se pierde la posibilidad de Redo.

```
Estado: A -> B -> C -> [Undo] -> B -> [Nuevo cambio D]
                               ↑
                     Redo ya no disponible
```
