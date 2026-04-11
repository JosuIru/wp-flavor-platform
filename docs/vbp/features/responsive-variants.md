# Responsive Variants (Variantes Responsive)

Sistema que permite definir overrides de propiedades para diferentes breakpoints (dispositivos), similar al diseno responsive de Figma.

## Descripcion

Las Responsive Variants permiten que un elemento tenga propiedades diferentes segun el tamano de pantalla, sin necesidad de duplicar elementos. Cada breakpoint puede tener su propia configuracion de layout, espaciado, tipografia, etc.

## Breakpoints Predefinidos

| Breakpoint | Min | Max | Ancho Canvas |
|------------|-----|-----|--------------|
| **desktop** | 1200px | - | 1200px |
| **laptop** | 992px | 1199px | 1024px |
| **tablet** | 768px | 991px | 768px |
| **mobile** | 0 | 767px | 375px |

## Cambiar Breakpoint

### Desde Toolbar

1. Click en los iconos de dispositivo en la toolbar
2. El canvas se redimensiona automaticamente

### Atajos de Teclado

| Atajo | Breakpoint |
|-------|------------|
| `1` | Desktop |
| `2` | Tablet |
| `3` | Mobile |

### Desde Inspector

En el selector de breakpoint del Inspector.

## Propiedades con Override

No todas las propiedades soportan overrides responsive. Las que si:

### Layout
- display
- flexDirection
- justifyContent
- alignItems
- gap
- gridTemplateColumns

### Sizing
- width, height
- minWidth, maxWidth
- minHeight, maxHeight

### Spacing
- margin (todos)
- padding (todos)

### Typography
- fontSize
- lineHeight
- letterSpacing
- textAlign

### Positioning
- position
- top, right, bottom, left
- zIndex

### Visibility
- hidden
- opacity
- visibility

### Order
- order
- flexGrow
- flexShrink

## Crear Override

### Metodo 1: Cambiar Valor en Breakpoint

1. Cambia al breakpoint deseado (ej: mobile)
2. Selecciona el elemento
3. Modifica la propiedad en el Inspector
4. El override se guarda automaticamente

### Metodo 2: Panel de Responsive

1. Selecciona el elemento
2. Abre el panel de Responsive Variants
3. Agrega override para propiedad especifica

## Indicadores Visuales

### En el Canvas

- Icono de breakpoint activo
- Elementos con overrides muestran indicador

### En el Inspector

- Las propiedades con override tienen icono especial
- Color diferente para valores heredados vs override

### En el Panel de Capas

- Icono junto a elementos con overrides

## Cascada de Valores

El sistema usa cascada Desktop-First por defecto:

```
Desktop -> Laptop -> Tablet -> Mobile
```

Si un valor no tiene override, hereda del breakpoint superior.

### Ejemplo

```javascript
// Desktop: fontSize = 48px
// Tablet: fontSize = 36px (override)
// Mobile: fontSize = 36px (hereda de tablet)

// O puedes especificar mobile tambien
// Mobile: fontSize = 28px (override)
```

## API JavaScript

### Acceder al Sistema

```javascript
const responsive = window.VBPResponsiveVariants;
```

### Propiedades

```javascript
responsive.currentBreakpoint  // 'desktop', 'tablet', etc.
responsive.canvasWidth        // Ancho actual del canvas
responsive.breakpoints        // Configuracion de breakpoints
responsive.cssOrderMode       // 'desktop-first' o 'mobile-first'
```

### Cambiar Breakpoint

```javascript
// Establecer breakpoint
responsive.setBreakpoint('tablet');

// Obtener breakpoint actual
const current = responsive.getBreakpoint();
```

### Obtener Overrides

```javascript
// Overrides de un elemento para todos los breakpoints
const overrides = responsive.getOverrides(elementId);

// Ejemplo:
{
    desktop: {},
    tablet: { fontSize: '36px', padding: '16px' },
    mobile: { fontSize: '28px', padding: '12px' }
}

// Override de una propiedad especifica
const fontSize = responsive.getPropertyOverride(elementId, 'typography.fontSize', 'tablet');
```

### Establecer Override

```javascript
// Establecer override para breakpoint especifico
responsive.setOverride(elementId, 'tablet', {
    fontSize: '36px',
    padding: '16px'
});

// Establecer propiedad individual
responsive.setPropertyOverride(elementId, 'typography.fontSize', '36px', 'tablet');
```

### Eliminar Override

```javascript
// Eliminar override de un breakpoint
responsive.removeOverride(elementId, 'tablet', 'fontSize');

// Eliminar todos los overrides de un breakpoint
responsive.removeAllOverrides(elementId, 'tablet');
```

### Copiar Entre Breakpoints

```javascript
// Copiar overrides de un breakpoint a otro
responsive.copyOverrides(elementId, 'desktop', 'tablet');

// Copiar solo ciertas propiedades
responsive.copyOverrides(elementId, 'desktop', 'tablet', ['fontSize', 'padding']);
```

### Obtener Valor Computado

```javascript
// Obtener valor efectivo (considerando herencia)
const computedValue = responsive.getComputedValue(elementId, 'fontSize', 'mobile');
```

## Eventos

```javascript
// Breakpoint cambiado
document.addEventListener('vbp:breakpoint:changed', (e) => {
    console.log('Nuevo breakpoint:', e.detail.breakpoint);
    console.log('Ancho canvas:', e.detail.canvasWidth);
});

// Override creado/modificado
document.addEventListener('vbp:responsive:override-changed', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Breakpoint:', e.detail.breakpoint);
    console.log('Propiedad:', e.detail.property);
});

// Canvas redimensionado
document.addEventListener('vbp:canvas:resized', (e) => {
    console.log('Nuevo ancho:', e.detail.width);
});
```

## CSS Generado

Los overrides se convierten a media queries:

```css
/* Base (desktop) */
.element {
    font-size: 48px;
    padding: 24px;
}

/* Tablet */
@media (min-width: 768px) and (max-width: 991px) {
    .element {
        font-size: 36px;
        padding: 16px;
    }
}

/* Mobile */
@media (max-width: 767px) {
    .element {
        font-size: 28px;
        padding: 12px;
    }
}
```

## Breakpoints Personalizados

Puedes agregar breakpoints adicionales:

```javascript
responsive.addBreakpoint({
    id: 'wide',
    min: 1400,
    max: null,
    icon: 'desktop',
    label: 'Wide Desktop',
    cssMediaQuery: '@media (min-width: 1400px)',
    canvasWidth: 1400
});
```

## Panel de Responsive

### Abrir Panel

- Click en el indicador de breakpoint
- Paleta de comandos: "Responsive Panel"

### Funcionalidades

- Ver todos los breakpoints
- Comparar valores entre breakpoints
- Copiar valores de un breakpoint a otro
- Ver que elementos tienen overrides
- Editar valores directamente

## Visualizador de Diferencias

Muestra que propiedades cambian entre breakpoints:

1. Selecciona elemento
2. Abre panel de Responsive
3. Activa "Show Differences"
4. Propiedades diferentes se resaltan

## Consideraciones

- Editar en un breakpoint solo afecta ese breakpoint
- Valores sin override heredan del breakpoint superior
- El canvas se redimensiona al cambiar breakpoint
- Los presets de diseino pueden tener valores responsive

## Buenas Practicas

1. **Disena Desktop First**: Empieza por desktop y ajusta para mobile
2. **Usa Menos Overrides**: Solo override lo necesario
3. **Tipografia Responsive**: Ajusta font-sizes para legibilidad
4. **Layout Flexible**: Usa flexbox/grid que adapten automaticamente
5. **Prueba Todos los Breakpoints**: Verifica el diseno en cada uno

## Solucionar Problemas

### Los overrides no se aplican

1. Verifica que guardaste los cambios
2. Comprueba que estas en el breakpoint correcto
3. Revisa la cascada de herencia

### El canvas no cambia de tamano

1. Verifica que el breakpoint cambio
2. Comprueba que canvasWidth esta configurado
3. Revisa si hay CSS que override el tamano

### CSS generado incorrecto

1. Verifica el orden de media queries
2. Comprueba la especificidad CSS
3. Revisa si hay conflictos con otros estilos
