# Panel de Capas (Layers)

Vista de arbol jerarquica de todos los elementos del documento con funciones de organizacion y visibilidad.

## Descripcion

El Panel de Capas muestra la estructura completa del documento en formato de arbol, permitiendo navegar, seleccionar, reorganizar y controlar la visibilidad de elementos de forma eficiente.

## Como Acceder

- Atajo: `Ctrl+L` (toggle)
- Menu: View > Layers
- Panel izquierdo, debajo del panel de bloques

## Interfaz

```
CAPAS
+-- [v] Section: Hero
|   +-- [v] Container
|   |   +-- [v] Heading: "Bienvenido"
|   |   +-- [v] Text: "Lorem ipsum..."
|   |   +-- [v] Button: "CTA"
+-- [v] Section: Features
|   +-- [v] Grid (3 cols)
|   |   +-- [v] Card 1
|   |   +-- [v] Card 2
|   |   +-- [x] Card 3 (oculto)
+-- [L] Footer (bloqueado)
```

### Iconos de Estado

| Icono | Significado |
|-------|-------------|
| [v] | Elemento visible |
| [x] | Elemento oculto |
| [L] | Elemento bloqueado |
| [>] | Rama colapsada |
| [v] | Rama expandida |

## Uso Basico

### Seleccionar Elementos

- Click: Selecciona elemento
- Shift+Click: Selecciona rango
- Ctrl+Click: Agrega/quita de seleccion
- Doble click: Expande/colapsa rama

### Reorganizar

- Drag and drop para mover elementos
- Indicadores muestran posicion de destino
- Mover dentro de un contenedor: suelta sobre el
- Mover antes/despues: suelta en el borde

### Visibilidad

- Click en icono de ojo: Toggle visibilidad
- Elementos ocultos se muestran atenuados
- Los hijos heredan la visibilidad del padre

### Bloqueo

- Click en icono de candado: Toggle bloqueo
- Elementos bloqueados no se pueden editar ni mover
- Util para proteger elementos finalizados

## Configuracion

### Opciones de Vista

```javascript
const layers = window.VBPLayers;

// Mostrar/ocultar elementos ocultos
layers.setShowHidden(true);

// Colapsar todos
layers.collapseAll();

// Expandir todos
layers.expandAll();

// Expandir hasta elemento
layers.expandToElement(elementId);

// Scroll a elemento
layers.scrollToElement(elementId);
```

### Filtrar Elementos

```javascript
// Buscar por nombre
layers.search('Button');

// Filtrar por tipo
layers.filterByType('section');

// Limpiar filtro
layers.clearFilter();
```

## API JavaScript

### VBPLayers

```javascript
const layers = window.VBPLayers;

// Navegacion
layers.expandAll();                      // Expandir todo
layers.collapseAll();                    // Colapsar todo
layers.expandToElement(elementId);       // Expandir hasta elemento
layers.toggleBranch(elementId);          // Toggle rama
layers.scrollToElement(elementId);       // Scroll a elemento

// Seleccion
layers.selectElement(elementId);         // Seleccionar
layers.selectElements(ids);              // Seleccion multiple
layers.selectAll();                      // Seleccionar todo
layers.deselectAll();                    // Deseleccionar todo

// Visibilidad
layers.showElement(elementId);           // Mostrar
layers.hideElement(elementId);           // Ocultar
layers.toggleVisibility(elementId);      // Toggle
layers.showAll();                        // Mostrar todos
layers.hideAll();                        // Ocultar todos

// Bloqueo
layers.lockElement(elementId);           // Bloquear
layers.unlockElement(elementId);         // Desbloquear
layers.toggleLock(elementId);            // Toggle
layers.lockAll();                        // Bloquear todos
layers.unlockAll();                      // Desbloquear todos

// Reorganizacion
layers.moveElement(elementId, targetId, position);  // 'before', 'after', 'inside'
layers.moveUp(elementId);                // Mover arriba
layers.moveDown(elementId);              // Mover abajo
layers.moveToTop(elementId);             // Mover al inicio
layers.moveToBottom(elementId);          // Mover al final

// Busqueda
layers.search(query);                    // Buscar
layers.filterByType(type);               // Filtrar por tipo
layers.clearFilter();                    // Limpiar filtro

// Estado
layers.getVisibleElements();             // Elementos visibles
layers.getHiddenElements();              // Elementos ocultos
layers.getLockedElements();              // Elementos bloqueados
layers.getExpandedBranches();            // Ramas expandidas
```

### Integracion con Store

```javascript
const store = Alpine.store('vbp');

// El panel de capas refleja automaticamente el estado
store.addElement(element);        // Aparece en capas
store.removeElement(elementId);   // Desaparece de capas
store.moveElement(id, newParent); // Se reorganiza en capas

// Seleccion sincronizada
store.select(elementId);          // Se resalta en capas
```

### Eventos

```javascript
// Elemento seleccionado en capas
document.addEventListener('vbp:layers:element:selected', (e) => {
    console.log('Seleccionado:', e.detail.elementId);
});

// Visibilidad cambiada
document.addEventListener('vbp:layers:visibility:changed', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Visible:', e.detail.visible);
});

// Bloqueo cambiado
document.addEventListener('vbp:layers:lock:changed', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Bloqueado:', e.detail.locked);
});

// Elemento movido
document.addEventListener('vbp:layers:element:moved', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Nuevo padre:', e.detail.newParentId);
    console.log('Posicion:', e.detail.newIndex);
});

// Rama expandida/colapsada
document.addEventListener('vbp:layers:branch:toggled', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Expandido:', e.detail.expanded);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+L` | Toggle panel de capas |
| `Ctrl+H` | Toggle visibilidad de seleccion |
| `Ctrl+Shift+H` | Ocultar otros |
| `Ctrl+Alt+L` | Toggle bloqueo de seleccion |
| `Alt+Arrow Up` | Seleccionar padre |
| `Alt+Arrow Down` | Seleccionar primer hijo |
| `Ctrl+]` | Traer adelante |
| `Ctrl+[` | Enviar atras |
| `Ctrl+Shift+]` | Traer al frente |
| `Ctrl+Shift+[` | Enviar al fondo |
| `Ctrl+.` | Toggle colapsar |

## Acciones Contextuales

Click derecho en un elemento del panel muestra:

| Accion | Descripcion |
|--------|-------------|
| Seleccionar | Seleccionar elemento |
| Duplicar | Duplicar elemento |
| Copiar | Copiar al portapapeles |
| Cortar | Cortar elemento |
| Pegar | Pegar contenido |
| Eliminar | Eliminar elemento |
| --- | --- |
| Mostrar/Ocultar | Toggle visibilidad |
| Bloquear/Desbloquear | Toggle bloqueo |
| --- | --- |
| Expandir todo | Expandir rama |
| Colapsar todo | Colapsar rama |
| --- | --- |
| Ir al elemento | Centrar en canvas |
| Renombrar | Cambiar etiqueta |

## Renombrar Elementos

Por defecto, los elementos muestran su tipo y contenido. Para personalizar:

1. Doble clic en el nombre
2. Escribe el nuevo nombre
3. Enter para confirmar

```javascript
// Via API
layers.renameElement(elementId, 'Mi seccion hero');
```

## Consideraciones

- Los cambios de visibilidad son solo en el editor
- El bloqueo no afecta al frontend
- La jerarquia refleja la estructura DOM
- Elementos dentro de Simbolos muestran indicador

## Solucionar Problemas

### El panel no se actualiza

1. Fuerza refresh con `layers.refresh()`
2. Recarga la pagina
3. Verifica errores en consola

### No puedo mover elementos

1. Verifica que el elemento no esta bloqueado
2. Verifica que el destino es valido
3. Algunos elementos no permiten hijos

### La busqueda no encuentra elementos

1. Verifica que no hay filtros activos
2. La busqueda es sensible al tipo de bloque
3. Prueba buscar por ID si conoces el identificador
