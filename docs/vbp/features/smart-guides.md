# Smart Guides (Guias Inteligentes)

Sistema de guias de alineacion automaticas que se muestran al arrastrar elementos, facilitando la alineacion precisa con otros elementos del canvas.

## Descripcion

Las Smart Guides detectan automaticamente los bordes y centros de elementos cercanos mientras arrastras, mostrando lineas de referencia para alinear elementos de forma precisa. Incluye snap automatico para "engancharse" a las posiciones sugeridas.

## Caracteristicas

- **Deteccion de bordes**: Izquierdo, derecho, superior, inferior
- **Deteccion de centros**: Horizontal y vertical
- **Snap automatico**: Los elementos se "enganchan" a las guias
- **Grid de 8px**: Alineacion opcional al grid base
- **Colores distintivos**: Rojo para centros, azul para bordes

## Como Usar

### Activacion

Las Smart Guides estan activas por defecto. Para activar/desactivar:

- Atajo: `Ctrl+Alt+G`
- Menu: View > Smart Guides
- Paleta de comandos: "Toggle Smart Guides"

### Durante el Arrastre

1. Comienza a arrastrar un elemento
2. Las guias aparecen automaticamente cuando te acercas a otro elemento
3. El elemento se "engancha" cuando esta a menos de 5px de una guia
4. Suelta para colocar el elemento en la posicion

### Tipos de Guias

| Color | Tipo | Descripcion |
|-------|------|-------------|
| Rojo | Centro | Alineacion al centro de otro elemento |
| Azul | Borde | Alineacion a un borde de otro elemento |
| Naranja | Spacing | Distancia igual a otra existente |
| Verde | Grid | Alineacion al grid base |

## Configuracion

### Ajustar Sensibilidad

La distancia de snap se puede ajustar:

```javascript
// Via store de Alpine
Alpine.store('vbp').snapDistance = 8; // pixels
```

### Desactivar Snap al Grid

```javascript
// Via store de Alpine
Alpine.store('vbp').snapToGrid = false;
```

### Cambiar Grid Base

```javascript
// Via store de Alpine
Alpine.store('vbp').gridSize = 16; // Por defecto es 8
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+G` | Activar/desactivar Smart Guides |
| `Ctrl+Shift+.` | Activar/desactivar Snap to Grid |
| `Alt+Shift+G` | Abrir configuracion de grid |

## API JavaScript

### Acceder al Sistema

```javascript
// Sistema principal
const smartGuides = window.VBPSmartGuides;

// Verificar estado
smartGuides.enabled       // true/false
smartGuides.snapDistance  // pixels
smartGuides.gridSize      // pixels
```

### Metodos Disponibles

```javascript
// Activar/desactivar
smartGuides.enable();
smartGuides.disable();
smartGuides.toggle();

// Configurar
smartGuides.setSnapDistance(10);
smartGuides.setGridSize(16);

// Limpiar guias visibles
smartGuides.clearGuides();
```

### Eventos

```javascript
// Escuchar cuando se activa una guia
document.addEventListener('vbp:smartguide:show', (event) => {
    console.log('Guia mostrada:', event.detail.type, event.detail.position);
});

// Escuchar snap
document.addEventListener('vbp:smartguide:snap', (event) => {
    console.log('Snap a:', event.detail.x, event.detail.y);
});
```

## CSS Personalizado

Los estilos de las guias se pueden personalizar:

```css
/* Guia de centro */
.vbp-smart-guide.center {
    background: #ef4444;
    width: 1px;
}

/* Guia de borde */
.vbp-smart-guide.edge {
    background: #3b82f6;
    width: 1px;
}

/* Etiqueta de distancia */
.vbp-smart-guide-label {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 2px;
}
```

## Consideraciones de Performance

- Las guias solo se calculan para elementos visibles en el viewport
- La deteccion usa un algoritmo optimizado O(n) con cache
- Se usa `requestAnimationFrame` para renderizado suave
- Las guias se limpian automaticamente al soltar el elemento

## Integracion con Otras Features

### Con Spacing Indicators

Las Smart Guides trabajan junto con los Spacing Indicators para mostrar distancias mientras arrastras.

### Con Constraints

Cuando un elemento tiene constraints activos, las Smart Guides respetan esas restricciones.

### Con Responsive Variants

Las guias se calculan basandose en el breakpoint activo.

## Solucionar Problemas

### Las guias no aparecen

1. Verifica que estan activadas: `Ctrl+Alt+G`
2. Comprueba la consola por errores
3. Asegurate de que hay otros elementos en el canvas

### El snap no funciona

1. Verifica que `snapToGrid` esta activo
2. Ajusta la sensibilidad (`snapDistance`)
3. El elemento destino debe ser visible

### Performance lenta

1. Reduce la cantidad de elementos en el canvas
2. Desactiva temporalmente las guias
3. Cierra paneles innecesarios
