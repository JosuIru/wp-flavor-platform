# Constraints (Restricciones/Anclaje)

Sistema de anclaje de elementos a los bordes de su contenedor padre, inspirado en Figma. Los elementos anclados mantienen su posicion relativa cuando se redimensiona el contenedor.

## Descripcion

Los Constraints definen como un elemento se comporta cuando su contenedor padre cambia de tamano. Puedes anclar elementos a bordes especificos, centrarlos, o hacer que se estiren para llenar el espacio disponible.

## Tipos de Constraints

### Horizontal

| Constraint | Comportamiento |
|------------|----------------|
| **Left** | Mantiene distancia al borde izquierdo |
| **Right** | Mantiene distancia al borde derecho |
| **Left + Right** | Se estira horizontalmente |
| **Center** | Se mantiene centrado horizontalmente |
| **Scale** | Escala proporcionalmente |

### Vertical

| Constraint | Comportamiento |
|------------|----------------|
| **Top** | Mantiene distancia al borde superior |
| **Bottom** | Mantiene distancia al borde inferior |
| **Top + Bottom** | Se estira verticalmente |
| **Center** | Se mantiene centrado verticalmente |
| **Scale** | Escala proporcionalmente |

## Como Usar

### Desde el Inspector

1. Selecciona un elemento
2. Abre la seccion "Constraints" en el Inspector
3. Haz clic en los puntos del diagrama para activar/desactivar anclajes

### Diagrama Visual

```
    [T]
     |
[L]--+--[R]
     |
    [B]
```

- **T**: Top (arriba)
- **R**: Right (derecha)
- **B**: Bottom (abajo)
- **L**: Left (izquierda)
- **Centro**: Click en el centro para centrar H/V

### Presets Rapidos

| Preset | Icono | Constraints |
|--------|-------|-------------|
| Superior izquierda | &#8598; | Top + Left |
| Superior centro | &#8593; | Top + Center H |
| Superior derecha | &#8599; | Top + Right |
| Centro izquierda | &#8592; | Center V + Left |
| Centro | &#9711; | Center H + Center V |
| Centro derecha | &#8594; | Center V + Right |
| Inferior izquierda | &#8601; | Bottom + Left |
| Inferior centro | &#8595; | Bottom + Center H |
| Inferior derecha | &#8600; | Bottom + Right |
| Estirar horizontal | &#8596; | Left + Right |
| Estirar vertical | &#8597; | Top + Bottom |
| Rellenar | &#9724; | All sides |

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+T` | Toggle constraint Top |
| `Ctrl+Alt+B` | Toggle constraint Bottom |
| `Ctrl+Alt+L` | Toggle constraint Left |
| `Ctrl+Alt+Right` | Toggle constraint Right |

## Indicadores Visuales

Cuando un elemento tiene constraints:

- **Lineas de conexion**: Se muestran lineas desde el elemento a los bordes anclados
- **Iconos de constraint**: Aparecen en las esquinas del elemento
- **Color de indicador**: Azul (`#3b82f6`) por defecto

## API JavaScript

### Acceder al Sistema

```javascript
const constraints = window.VBPConstraints;
```

### Obtener Constraints de un Elemento

```javascript
const elementConstraints = constraints.getConstraints(elementId);

// Ejemplo de resultado:
{
    horizontal: 'left',      // 'left', 'right', 'center', 'stretch', 'scale'
    vertical: 'top',         // 'top', 'bottom', 'center', 'stretch', 'scale'
    left: 20,                // Distancia en px (si aplica)
    right: null,
    top: 16,
    bottom: null
}
```

### Establecer Constraints

```javascript
// Establecer constraints completos
constraints.setConstraints(elementId, {
    horizontal: 'center',
    vertical: 'top'
});

// Aplicar preset
constraints.applyPreset(elementId, 'top-center');

// Toggle individual
constraints.toggleConstraint(elementId, 'left');
constraints.toggleConstraint(elementId, 'right');
```

### Presets Disponibles

```javascript
// Obtener lista de presets
const presets = constraints.getPresets();

// Ejemplo:
{
    'top-left': { horizontal: 'left', vertical: 'top', label: 'Superior izquierda', icon: '...' },
    'center': { horizontal: 'center', vertical: 'center', label: 'Centro', icon: '...' },
    'fill': { horizontal: 'stretch', vertical: 'stretch', label: 'Rellenar', icon: '...' },
    // ...
}
```

### Eventos

```javascript
// Cuando cambian los constraints
document.addEventListener('vbp:constraints:changed', (event) => {
    console.log('Elemento:', event.detail.elementId);
    console.log('Constraints:', event.detail.constraints);
});

// Cuando se activan los indicadores visuales
document.addEventListener('vbp:constraints:indicators:shown', (event) => {
    console.log('Mostrando indicadores para:', event.detail.elementId);
});
```

## Funcionamiento Interno

### Calculo de Posicion

Cuando el padre se redimensiona:

1. Se calcula el nuevo tamano del padre
2. Para cada hijo con constraints:
   - Si `left`: mantiene `element.left`
   - Si `right`: recalcula `element.left = parent.width - element.width - constraints.right`
   - Si `stretch`: `element.width = parent.width - constraints.left - constraints.right`
   - Si `center`: `element.left = (parent.width - element.width) / 2`

### ResizeObserver

El sistema usa `ResizeObserver` para detectar cambios en el tamano del contenedor padre y aplicar los constraints automaticamente.

## Integracion con Responsive Variants

Los constraints pueden tener valores diferentes por breakpoint:

- Desktop: `center` + `top`
- Tablet: `stretch` + `top`
- Mobile: `stretch` + `stretch`

Cada breakpoint almacena su propia configuracion de constraints.

## CSS Generado

Los constraints se traducen a CSS:

```css
/* Left + Top */
.element {
    position: absolute;
    left: 20px;
    top: 16px;
}

/* Center horizontally */
.element {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

/* Stretch horizontal */
.element {
    position: absolute;
    left: 20px;
    right: 20px;
    width: auto;
}
```

## Consideraciones

- Los constraints solo aplican a elementos con `position: absolute` o `fixed`
- El contenedor padre debe tener `position: relative` (o similar)
- Los constraints de `scale` multiplican las dimensiones proporcionalmente
- Los elementos con `display: flex` o `grid` pueden comportarse diferente

## Casos de Uso

### Header Fijo

```
Constraints: Left + Right + Top
```
El header se estira horizontalmente y permanece arriba.

### Modal Centrado

```
Constraints: Center H + Center V
```
El modal permanece centrado sin importar el tamano de la ventana.

### Sidebar con Altura Completa

```
Constraints: Left + Top + Bottom
```
La sidebar se estira verticalmente y permanece a la izquierda.

### Boton Flotante

```
Constraints: Right + Bottom
```
El boton permanece en la esquina inferior derecha.

## Solucionar Problemas

### El elemento no se reposiciona

1. Verifica que el elemento tiene `position: absolute` o similar
2. Comprueba que el padre tiene `position: relative`
3. Revisa que los constraints estan configurados

### El stretch no funciona

1. Verifica que tienes ambos constraints opuestos activos
2. Comprueba que no hay `width` o `height` fijos que interfieran
3. Revisa que el padre tiene dimensiones definidas

### Los indicadores no aparecen

1. Selecciona el elemento
2. Verifica que `constraints.enabled` es `true`
3. Comprueba que el contenedor de indicadores existe
