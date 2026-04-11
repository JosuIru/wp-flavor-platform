# Zoom to Selection (Zoom a la Seleccion)

Funcionalidad para hacer zoom automatico y centrar la vista en los elementos seleccionados actualmente.

## Descripcion

Permite enfocar rapidamente la vista del canvas en los elementos seleccionados, calculando automaticamente el nivel de zoom optimo y centrando la vista. Especialmente util en documentos grandes o cuando trabajas con elementos pequenos.

## Como Usar

### Zoom a la Seleccion Actual

1. Selecciona uno o mas elementos
2. Presiona `Shift+2`
3. El canvas se ajusta para mostrar la seleccion centrada

### Zoom a Todo (Fit All)

Para ver todos los elementos del documento:
- Presiona `Shift+0`
- El zoom se ajusta para mostrar todo el contenido

### Doble Clic en Minimap

1. Abre el panel Minimap si no esta visible
2. Haz doble clic sobre un area
3. El canvas hace zoom y centra esa area

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Shift+2` | Zoom a la seleccion |
| `Shift+0` | Zoom para ver todo (Fit All) |
| `Shift+1` | Zoom al 100% animado |
| `Ctrl+0` | Resetear zoom |
| `Ctrl+1` | Zoom 100% |
| `Ctrl+2` | Zoom 200% |
| `Ctrl+5` | Zoom 50% |
| `Ctrl++` / `Ctrl+=` | Zoom In |
| `Ctrl+-` | Zoom Out |
| `Alt+Enter` | Centrar seleccion en viewport |

## Comportamiento

### Calculo del Zoom

El sistema calcula:

1. **Bounding Box**: El rectangulo que contiene todos los elementos seleccionados
2. **Padding**: Agrega un margen visual (por defecto 50px)
3. **Zoom Optimo**: El nivel de zoom que hace que el bounding box encaje en el viewport
4. **Limite Maximo**: No supera el 200% para evitar pixelacion

### Animacion

La transicion de zoom incluye:
- Duracion: 300ms
- Easing: ease-out
- Transicion suave de zoom y posicion simultaneamente

### Elementos Agrupados

Cuando hay multiples elementos seleccionados:
- Se calcula el bounding box que los contiene a todos
- El zoom se ajusta para mostrar el grupo completo
- Util para trabajar con componentes complejos

## API JavaScript

### Metodos del Store

```javascript
const store = Alpine.store('vbp');

// Zoom a la seleccion actual
store.zoomToSelection();

// Zoom a elementos especificos
store.zoomToElements([id1, id2, id3]);

// Zoom para ver todo
store.zoomToFit();

// Centrar sin cambiar zoom
store.centerOnSelection();

// Centrar en un elemento especifico
store.centerOnElement(elementId);
```

### Metodos del Canvas

```javascript
// Acceder al modulo de canvas
const canvas = window.VBPCanvas || window.vbpCanvas;

// Calcular bounding box de elementos
const bbox = canvas.getBoundingBox([id1, id2]);
// Retorna: { x, y, width, height }

// Aplicar zoom con animacion
canvas.animateZoomTo(zoomLevel, centerX, centerY, duration);

// Zoom a un bounding box
canvas.zoomToBoundingBox(bbox, padding);
```

### Eventos

```javascript
// Cuando cambia el zoom
document.addEventListener('vbp:zoom:changed', (event) => {
    console.log('Nuevo zoom:', event.detail.zoom);
    console.log('Centro:', event.detail.center);
});

// Cuando termina la animacion de zoom
document.addEventListener('vbp:zoom:animation:complete', () => {
    console.log('Animacion completada');
});
```

## Configuracion

### Ajustar Padding

El espacio alrededor de la seleccion:

```javascript
// Via store
Alpine.store('vbp').zoomPadding = 80; // pixels
```

### Ajustar Zoom Maximo

```javascript
// Limitar el zoom maximo al hacer "zoom to selection"
Alpine.store('vbp').maxZoomToSelection = 150; // 150%
```

### Desactivar Animacion

```javascript
// Para zoom instantaneo
Alpine.store('vbp').animateZoom = false;
```

## Integracion con Minimap

El panel Minimap muestra una vista en miniatura del canvas completo:

- **Vista actual**: Rectangulo indicando el area visible
- **Navegacion**: Clic para mover la vista
- **Zoom rapido**: Doble clic para zoom a esa area

### Sincronizacion

El minimap se actualiza automaticamente cuando:
- Cambias el zoom
- Mueves la vista (pan)
- Agregas o eliminas elementos
- Cambias la seleccion

## Casos de Uso

### Encontrar Elemento Perdido

1. Usa el panel de Capas para seleccionar el elemento
2. Presiona `Shift+2`
3. El canvas te lleva directamente al elemento

### Trabajar con Detalles

1. Selecciona un elemento pequeno
2. `Shift+2` para hacer zoom
3. Edita los detalles
4. `Ctrl+0` para volver al zoom normal

### Presentar Diseno

1. `Shift+0` para ver todo el documento
2. Usa `Shift+2` para enfocar areas especificas
3. Ideal para mostrar el diseno a clientes

### Navegacion Rapida

1. Selecciona diferentes secciones desde Capas
2. `Shift+2` para saltar entre ellas
3. Mas rapido que hacer scroll manual

## Consideraciones

- El zoom maximo por defecto es 200%
- El zoom minimo es 10%
- Los elementos ocultos no afectan el calculo del bounding box
- Los elementos fuera del viewport principal no se consideran
- Las animaciones se desactivan si el usuario tiene "reduce motion" activo

## Solucionar Problemas

### El zoom no funciona

1. Verifica que hay elementos seleccionados
2. Comprueba que los elementos son visibles (no ocultos)
3. Verifica la consola por errores

### La animacion es lenta

1. Reduce la cantidad de elementos en el canvas
2. Verifica que no hay animaciones CSS complejas
3. Prueba desactivar la animacion temporalmente

### El encuadre no es correcto

1. Verifica que el padding esta configurado correctamente
2. Comprueba que no hay elementos con dimensiones anormales
3. Revisa si hay transformaciones CSS que afecten el calculo
