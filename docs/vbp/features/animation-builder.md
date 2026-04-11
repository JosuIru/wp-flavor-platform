# Animation Builder (Constructor de Animaciones)

Panel visual tipo timeline para crear animaciones CSS personalizadas con keyframes, curvas de easing y triggers configurables.

## Descripcion

El Animation Builder permite crear animaciones complejas sin escribir codigo. Incluye un timeline visual para definir keyframes, presets de animaciones comunes, editor de curvas bezier y configuracion de triggers.

## Abrir el Animation Builder

- Atajo: `Ctrl+Alt+A`
- Menu: View > Animation Builder
- Inspector: Seccion "Animaciones" > "Crear animacion"

## Interfaz

### Timeline

```
0%    25%    50%    75%    100%
|------+------+------+------|
[K]    [K]    [K]    [K]   [K]

K = Keyframe
```

- **Agregar keyframe**: Click en la linea del timeline
- **Mover keyframe**: Arrastra horizontalmente
- **Editar keyframe**: Click para seleccionar y editar propiedades
- **Eliminar keyframe**: Selecciona y presiona Delete

### Panel de Propiedades

Propiedades animables organizadas por categoria:

- **Transform**: translateX/Y/Z, rotate, scale, skew
- **Appearance**: opacity
- **Colors**: backgroundColor, color, borderColor
- **Effects**: boxShadow, filter

### Configuracion

- **Duracion**: Tiempo total de la animacion
- **Easing**: Curva de aceleracion
- **Iteraciones**: Numero de repeticiones (o infinito)
- **Direccion**: Normal, reverse, alternate
- **Delay**: Retraso antes de iniciar

## Propiedades Animables

| Propiedad | Tipo | Rango | Unidad |
|-----------|------|-------|--------|
| opacity | number | 0 - 1 | - |
| translateX | number | -500 - 500 | px |
| translateY | number | -500 - 500 | px |
| translateZ | number | -500 - 500 | px |
| rotate | number | -360 - 360 | deg |
| rotateX | number | -360 - 360 | deg |
| rotateY | number | -360 - 360 | deg |
| scale | number | 0 - 3 | - |
| scaleX | number | 0 - 3 | - |
| scaleY | number | 0 - 3 | - |
| skewX | number | -45 - 45 | deg |
| skewY | number | -45 - 45 | deg |
| backgroundColor | color | - | hex/rgb |
| color | color | - | hex/rgb |
| borderColor | color | - | hex/rgb |
| boxShadow | shadow | - | css |
| filter | filter | - | css |

## Presets de Animacion

### Fade

| Nombre | Descripcion |
|--------|-------------|
| fadeIn | Aparece con opacity 0->1 |
| fadeOut | Desaparece con opacity 1->0 |
| fadeInUp | Aparece subiendo |
| fadeInDown | Aparece bajando |
| fadeInLeft | Aparece desde la izquierda |
| fadeInRight | Aparece desde la derecha |

### Slide

| Nombre | Descripcion |
|--------|-------------|
| slideInUp | Desliza hacia arriba |
| slideInDown | Desliza hacia abajo |
| slideInLeft | Desliza desde la izquierda |
| slideInRight | Desliza desde la derecha |

### Zoom

| Nombre | Descripcion |
|--------|-------------|
| zoomIn | Crece desde pequeno |
| zoomOut | Aparece desde grande |

### Efectos

| Nombre | Descripcion |
|--------|-------------|
| bounce | Rebote |
| pulse | Pulso (escala) |
| shake | Vibracion horizontal |
| swing | Balanceo |
| flip | Voltear |
| rubberBand | Efecto elastico |
| wobble | Tambaleo |

## Curvas de Easing

### Presets

| Nombre | Bezier |
|--------|--------|
| linear | linear |
| ease | ease |
| ease-in | ease-in |
| ease-out | ease-out |
| ease-in-out | ease-in-out |
| bounce | cubic-bezier(0.68, -0.55, 0.265, 1.55) |
| elastic | cubic-bezier(0.68, -0.6, 0.32, 1.6) |
| sharp | cubic-bezier(0.4, 0, 0.6, 1) |
| smooth | cubic-bezier(0.25, 0.1, 0.25, 1) |
| overshoot | cubic-bezier(0.34, 1.56, 0.64, 1) |

### Editor de Bezier

1. Selecciona "Custom" en easing
2. Arrastra los puntos de control
3. Previsualiza el resultado

## Triggers (Disparadores)

### Tipos de Trigger

| Trigger | Descripcion |
|---------|-------------|
| load | Al cargar la pagina |
| hover | Al pasar el cursor |
| click | Al hacer click |
| scroll | Al entrar en viewport |
| scroll-out | Al salir del viewport |

### Configuracion de Scroll

- **Offset**: Porcentaje del viewport donde se activa
- **Once**: Ejecutar solo una vez
- **Reverse**: Invertir al salir

## API JavaScript

### Acceder al Sistema

```javascript
// Sistema principal
const animBuilder = window.VBPAnimationBuilder;
```

### Obtener Animaciones de un Elemento

```javascript
const animations = animBuilder.getAnimations(elementId);

// Ejemplo de resultado:
[
    {
        id: 'anim_123',
        name: 'Fade In Up',
        duration: '0.6s',
        easing: 'ease-out',
        iterations: 1,
        keyframes: [
            { offset: 0, properties: { opacity: 0, translateY: 30 } },
            { offset: 100, properties: { opacity: 1, translateY: 0 } }
        ],
        trigger: 'scroll',
        triggerConfig: { offset: 20, once: true }
    }
]
```

### Crear Animacion

```javascript
animBuilder.createAnimation(elementId, {
    name: 'Mi Animacion',
    duration: '0.5s',
    easing: 'ease-out',
    keyframes: [
        { offset: 0, properties: { opacity: 0 } },
        { offset: 100, properties: { opacity: 1 } }
    ],
    trigger: 'load'
});
```

### Aplicar Preset

```javascript
animBuilder.applyPreset(elementId, 'fadeInUp', {
    duration: '0.8s',
    trigger: 'scroll'
});
```

### Previsualizar

```javascript
// Previsualizar animacion
animBuilder.preview(elementId);

// Detener preview
animBuilder.stopPreview();
```

### Exportar CSS

```javascript
// Obtener CSS de la animacion
const css = animBuilder.exportCSS(animationId);

// Ejemplo de resultado:
/*
@keyframes vbp_anim_123 {
    0% {
        opacity: 0;
        transform: translateY(30px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.element-class {
    animation: vbp_anim_123 0.6s ease-out forwards;
}
*/
```

## Estructura de Keyframe

```javascript
{
    offset: 50,           // Posicion en timeline (0-100)
    properties: {
        opacity: 0.5,
        translateX: 100,
        rotate: 45,
        backgroundColor: '#ff0000'
    },
    easing: 'ease-in'     // Opcional: easing hasta el siguiente keyframe
}
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+A` | Abrir Animation Builder |
| `Space` | Play/Pause preview |
| `Enter` | Agregar keyframe en posicion actual |
| `Delete` | Eliminar keyframe seleccionado |
| `Ctrl+C` | Copiar animacion |
| `Ctrl+V` | Pegar animacion |

## Integracion con Prototype Mode

Las animaciones creadas pueden usarse en interacciones del Prototype Mode:

1. Crea una animacion con Animation Builder
2. En Prototype Mode, selecciona la accion "Animar"
3. Elige la animacion del dropdown

## Consideraciones

- Las animaciones se guardan con el documento VBP
- Multiples animaciones pueden aplicarse al mismo elemento
- Las animaciones CSS se generan al publicar
- Los triggers de scroll usan IntersectionObserver

## Solucionar Problemas

### La animacion no se reproduce

1. Verifica que el trigger esta configurado
2. Comprueba que el elemento es visible
3. Para scroll: verifica que el elemento esta fuera del viewport inicial

### Performance lenta

1. Evita animar propiedades que causan repaint (width, height)
2. Prefiere transform y opacity
3. Usa `will-change` con moderacion

### El export CSS no funciona

1. Verifica que hay keyframes definidos
2. Comprueba que la duracion no es 0
3. Revisa la consola por errores
