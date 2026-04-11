# Canvas

El area de trabajo central del editor donde se visualizan y editan los elementos.

## Descripcion

El Canvas es el espacio principal de diseno donde los usuarios arrastran, posicionan y editan elementos. Soporta zoom, pan, drag and drop, redimensionamiento y edicion inline de contenido.

## Como Acceder

El Canvas es el componente central del editor, visible automaticamente al abrir VBP.

## Funcionalidades

### Drag and Drop

- Arrastra bloques desde el panel izquierdo al canvas
- Reorganiza elementos arrastrando y soltando
- Indicadores visuales muestran donde se insertara el elemento

### Seleccion de Elementos

| Accion | Resultado |
|--------|-----------|
| Click | Selecciona elemento |
| Click + Shift | Agrega a seleccion |
| Click + Ctrl | Toggle seleccion |
| Rectangulo de seleccion | Seleccion multiple |
| Escape | Deseleccionar todo |

### Zoom y Navegacion

| Accion | Resultado |
|--------|-----------|
| Ctrl + Scroll | Zoom in/out |
| Space + Drag | Pan del canvas |
| Ctrl + 0 | Reset zoom |
| Ctrl + 1 | Zoom 100% |
| Shift + 0 | Fit to view |

### Edicion Inline

- Doble clic en texto para editar
- Enter para confirmar
- Escape para cancelar

### Redimensionamiento

- Handles en esquinas y bordes
- Shift para mantener proporcion
- Alt para redimensionar desde el centro

## Configuracion

### Opciones de Canvas

```javascript
// Acceder via store
const store = Alpine.store('vbp');

// Configurar zoom
store.setZoom(150);           // 150%
store.zoomIn();               // +10%
store.zoomOut();              // -10%
store.zoomToFit();            // Ajustar a vista
store.zoomToSelection();      // Zoom a seleccion

// Cambiar breakpoint
store.devicePreview = 'tablet';  // 'desktop', 'tablet', 'mobile'
```

### Grid y Snap

```javascript
// Configurar grid
const canvas = window.VBPCanvas;

canvas.setGridSize(8);        // Grid de 8px
canvas.toggleSnapToGrid();    // Toggle snap
canvas.toggleSmartGuides();   // Toggle guias
```

## API JavaScript

### VBPCanvas

```javascript
const canvas = window.VBPCanvas;

// Zoom
canvas.getZoom();                    // Obtener zoom actual
canvas.setZoom(level);               // Establecer zoom
canvas.zoomIn();                     // Incrementar zoom
canvas.zoomOut();                    // Decrementar zoom
canvas.zoomToFit();                  // Ajustar a viewport
canvas.zoomToSelection();            // Zoom a seleccion
canvas.zoomToElement(elementId);     // Zoom a elemento

// Scroll/Pan
canvas.scrollTo(x, y);               // Scroll a posicion
canvas.scrollToElement(elementId);   // Scroll a elemento
canvas.centerElement(elementId);     // Centrar elemento
canvas.getScrollPosition();          // Obtener posicion actual

// Seleccion
canvas.selectElement(elementId);     // Seleccionar
canvas.selectElements(ids);          // Seleccion multiple
canvas.deselectAll();                // Deseleccionar
canvas.getSelectedElements();        // Obtener seleccionados

// Drag and Drop
canvas.startDrag(elementId);         // Iniciar drag
canvas.endDrag();                    // Finalizar drag
canvas.cancelDrag();                 // Cancelar drag

// Redimensionamiento
canvas.startResize(elementId, handle); // Iniciar resize
canvas.endResize();                    // Finalizar resize

// Viewport
canvas.getViewportBounds();          // Limites visibles
canvas.isElementVisible(elementId);  // Esta visible?
canvas.getVisibleElements();         // Elementos visibles
```

### Eventos del Canvas

```javascript
// Clic en elemento
document.addEventListener('vbp:canvas:element:click', (e) => {
    console.log('Click en:', e.detail.elementId);
});

// Drag iniciado
document.addEventListener('vbp:canvas:drag:start', (e) => {
    console.log('Drag de:', e.detail.elementId);
});

// Drag finalizado
document.addEventListener('vbp:canvas:drag:end', (e) => {
    console.log('Soltado en:', e.detail.dropTarget);
});

// Zoom cambiado
document.addEventListener('vbp:zoom:changed', (e) => {
    console.log('Nuevo zoom:', e.detail.zoom);
});

// Scroll/Pan
document.addEventListener('vbp:canvas:scroll', (e) => {
    console.log('Posicion:', e.detail.x, e.detail.y);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl++` | Zoom in |
| `Ctrl+-` | Zoom out |
| `Ctrl+0` | Reset zoom |
| `Ctrl+1` | Zoom 100% |
| `Ctrl+2` | Zoom 200% |
| `Shift+0` | Fit to view |
| `Shift+2` | Zoom to selection |
| `Space` | Modo pan |
| `M` | Herramienta medida |
| `Ctrl+Shift+.` | Toggle snap to grid |
| `Ctrl+Alt+G` | Toggle smart guides |
| `Alt+Enter` | Centrar en viewport |

## Breakpoints

El canvas soporta preview en diferentes breakpoints:

| Breakpoint | Ancho | Atajo |
|------------|-------|-------|
| Desktop | 1200px+ | `1` |
| Tablet | 768px | `2` |
| Mobile | 375px | `3` |

```javascript
// Cambiar breakpoint
const store = Alpine.store('vbp');
store.devicePreview = 'tablet';

// O via responsive variants
window.VBPResponsiveVariants.setBreakpoint('tablet');
```

## Grid y Guias

### Grid

- Configurable (8px por defecto)
- Snap automatico al arrastrar
- Toggle con `Ctrl+Shift+.`

### Smart Guides

- Alineacion automatica con otros elementos
- Muestra lineas de guia al arrastrar
- Colores: rojo para centro, azul para bordes

### Reglas

- Toggle con `Ctrl+Alt+Shift+R`
- Unidades: px, rem, %
- Click para crear guia manual

## Consideraciones de Performance

- Virtual scrolling para documentos grandes
- Throttling en eventos de mouse
- Lazy rendering de elementos fuera del viewport
- RequestAnimationFrame para animaciones

## Solucionar Problemas

### El canvas no responde

1. Verifica que no hay errores JS en consola
2. Comprueba que el canvas tiene foco
3. Refresca la pagina

### El drag and drop no funciona

1. Verifica que SortableJS esta cargado
2. Comprueba permisos del elemento
3. Revisa que no hay bloqueos de colaboracion

### El zoom se comporta extraño

1. Limpia cache del navegador
2. Verifica nivel de zoom del navegador (debe ser 100%)
3. Comprueba configuracion de DPI del sistema
