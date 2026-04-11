# Spacing Indicators (Indicadores de Espaciado)

Sistema que muestra la distancia en pixeles entre el elemento seleccionado y otros elementos del canvas. Util para mantener espaciados consistentes en el diseno.

## Descripcion

Los Spacing Indicators muestran flechas bidireccionales con valores numericos que indican la distancia exacta entre elementos. Se activan con `Alt+hover` sobre otros elementos o automaticamente durante el arrastre.

## Caracteristicas

- **Distancia horizontal y vertical**: Muestra ambas medidas
- **Flechas visuales**: Indica claramente la direccion de la medida
- **Valores en pixeles**: Muestra el valor exacto
- **Activacion intuitiva**: Alt+hover o durante arrastre
- **Color distintivo**: Naranja para facil identificacion

## Como Usar

### Modo Manual (Alt+Hover)

1. Selecciona un elemento en el canvas
2. Mantiene presionada la tecla `Alt`
3. Pasa el cursor sobre otro elemento
4. Aparecen los indicadores de distancia

### Modo Automatico (Arrastre)

1. Comienza a arrastrar un elemento
2. Los indicadores aparecen automaticamente
3. Muestran la distancia a elementos cercanos

## Tipos de Indicadores

| Direccion | Descripcion |
|-----------|-------------|
| Horizontal | Distancia izquierda-derecha entre elementos |
| Vertical | Distancia arriba-abajo entre elementos |
| Diagonal | Cuando no hay alineacion directa |

## Configuracion

### Activar/Desactivar

```javascript
// Via sistema
window.VBPSpacingIndicators.enabled = true; // o false
```

### Cambiar Color

Los indicadores usan el color naranja por defecto (`#f97316`):

```css
.vbp-spacing-indicator {
    background-color: #f97316;
}

.vbp-spacing-indicator-line {
    stroke: #f97316;
}
```

## API JavaScript

### Acceder al Sistema

```javascript
const spacingIndicators = window.VBPSpacingIndicators;
```

### Propiedades

```javascript
spacingIndicators.enabled        // Estado actual
spacingIndicators.indicators     // Array de indicadores activos
spacingIndicators.container      // Contenedor DOM
spacingIndicators.activeElementId // ID del elemento seleccionado
```

### Metodos

```javascript
// Inicializar
spacingIndicators.init();

// Mostrar spacing entre dos elementos
spacingIndicators.showSpacing(fromElementId, toElementId);

// Mostrar durante arrastre
spacingIndicators.showDragSpacing(dragDetail);

// Limpiar indicadores
spacingIndicators.clearIndicators();
```

### Eventos

```javascript
// El sistema escucha estos eventos automaticamente:

// Movimiento del mouse con Alt
document.addEventListener('mousemove', handler);

// Eventos de arrastre
document.addEventListener('vbp:drag:move', handler);
document.addEventListener('vbp:drag:end', handler);

// Cambio de seleccion
document.addEventListener('vbp:selection:changed', handler);
```

## Estructura del Indicador

Cada indicador se compone de:

```html
<div class="vbp-spacing-indicator">
    <div class="vbp-spacing-line"></div>
    <div class="vbp-spacing-arrow vbp-spacing-arrow-start"></div>
    <div class="vbp-spacing-arrow vbp-spacing-arrow-end"></div>
    <span class="vbp-spacing-value">24px</span>
</div>
```

## CSS Personalizado

```css
/* Contenedor de indicadores */
.vbp-spacing-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9999;
}

/* Linea del indicador */
.vbp-spacing-line {
    position: absolute;
    background: rgba(249, 115, 22, 0.8);
}

/* Linea horizontal */
.vbp-spacing-line.horizontal {
    height: 1px;
}

/* Linea vertical */
.vbp-spacing-line.vertical {
    width: 1px;
}

/* Flechas */
.vbp-spacing-arrow {
    position: absolute;
    width: 0;
    height: 0;
    border: 4px solid transparent;
}

/* Valor numerico */
.vbp-spacing-value {
    position: absolute;
    background: rgba(249, 115, 22, 0.9);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
```

## Integracion con Smart Guides

Los Spacing Indicators trabajan en conjunto con Smart Guides:

- Smart Guides muestran **donde alinear**
- Spacing Indicators muestran **cuanta distancia hay**

Durante el arrastre, ambos sistemas funcionan simultaneamente para proporcionar una experiencia completa de posicionamiento.

## Casos de Uso

### Mantener Espaciado Consistente

1. Coloca el primer elemento
2. Arrastra el segundo elemento
3. Usa los indicadores para igualar el espaciado

### Verificar Margenes

1. Selecciona un elemento
2. Alt+hover sobre los bordes del contenedor
3. Verifica que los margenes son correctos

### Distribuir Elementos

1. Selecciona elementos con espaciado irregular
2. Usa los indicadores como referencia
3. Ajusta posiciones para igualar distancias

## Consideraciones de Performance

- Los indicadores solo se muestran para elementos visibles
- Se limpian automaticamente al soltar Alt o terminar el arrastre
- Usan CSS transforms para animaciones suaves
- El sistema tiene throttling para evitar recalculos excesivos

## Solucionar Problemas

### Los indicadores no aparecen

1. Verifica que hay un elemento seleccionado
2. Asegurate de mantener presionada la tecla Alt
3. El elemento sobre el que pasas debe ser diferente al seleccionado

### Los valores son incorrectos

1. Verifica el zoom actual del canvas
2. Los valores se calculan en pixeles del documento, no del viewport
3. Comprueba que no hay transformaciones CSS que afecten

### Performance lenta

1. Reduce la cantidad de elementos en el canvas
2. Verifica que no hay otros listeners pesados en mousemove
