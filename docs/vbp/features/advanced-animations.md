# Advanced Animations (Animaciones Avanzadas)

Sistema extendido de animaciones con soporte para stagger, motion path, spring physics y gestos interactivos.

## Descripcion

Las Animaciones Avanzadas extienden el Animation Builder basico con capacidades profesionales: animaciones secuenciales (stagger), trayectorias de movimiento (motion path), fisica de resorte (spring), y respuesta a gestos del usuario.

## Como Acceder

- Animation Builder > Tab "Avanzado"
- Atajo: `Ctrl+Alt+A` > Tab "Avanzado"
- Inspector > Animaciones > Modo Avanzado

## Tipos de Animaciones Avanzadas

### 1. Stagger (Secuencial)

Anima multiples elementos con un retraso entre cada uno.

```javascript
{
    type: 'stagger',
    targets: '.card',          // Selector de hijos
    animation: {
        from: { opacity: 0, y: 30 },
        to: { opacity: 1, y: 0 }
    },
    stagger: {
        each: 0.1,             // Delay entre elementos
        from: 'start',         // 'start', 'end', 'center', 'edges', 'random'
        grid: [3, 3],          // Grid para stagger 2D
        axis: 'x'              // 'x', 'y', null para 2D
    },
    duration: 0.5,
    easing: 'ease-out'
}
```

### 2. Motion Path (Trayectoria)

Anima elementos a lo largo de una trayectoria SVG.

```javascript
{
    type: 'motion-path',
    path: 'M0,0 C50,100 150,0 200,100',  // SVG path
    autoRotate: true,          // Rotar siguiendo la trayectoria
    align: 'center',           // 'center', 'start', 'end'
    alignOrigin: [0.5, 0.5],   // Punto de anclaje
    duration: 2,
    ease: 'power1.inOut',
    repeat: -1,                // -1 = infinito
    yoyo: true                 // Ida y vuelta
}
```

### 3. Spring Physics (Fisica)

Animaciones con fisica de resorte realista.

```javascript
{
    type: 'spring',
    properties: {
        x: 100,
        scale: 1.2,
        rotate: 45
    },
    spring: {
        stiffness: 100,        // Rigidez (1-1000)
        damping: 10,           // Amortiguacion (1-100)
        mass: 1,               // Masa
        velocity: 0            // Velocidad inicial
    }
}
```

### 4. Gesture Animations (Gestos)

Animaciones que responden a interacciones del usuario.

```javascript
{
    type: 'gesture',
    gesture: 'drag',           // 'drag', 'hover', 'press', 'tap'
    animation: {
        whileHover: { scale: 1.05 },
        whileTap: { scale: 0.95 },
        whileDrag: { scale: 1.1 }
    },
    drag: {
        axis: 'x',             // 'x', 'y', null (libre)
        bounds: { left: -100, right: 100 },
        elastic: 0.2           // Elasticidad en limites
    }
}
```

## API JavaScript

### VBPAdvancedAnimations

```javascript
const advAnim = window.VBPAdvancedAnimations;

// Crear stagger
advAnim.createStagger(parentId, {
    targets: '.child',
    animation: { opacity: [0, 1], y: [20, 0] },
    stagger: { each: 0.1, from: 'center' },
    duration: 0.4
});

// Crear motion path
advAnim.createMotionPath(elementId, {
    path: '#my-svg-path',
    duration: 3,
    autoRotate: true
});

// Crear spring
advAnim.createSpring(elementId, {
    to: { x: 100, scale: 1.2 },
    spring: { stiffness: 120, damping: 14 }
});

// Crear gesture animation
advAnim.createGesture(elementId, {
    gesture: 'drag',
    dragAxis: 'x',
    onDrag: (e) => console.log('Dragging', e.offset)
});

// Obtener animaciones
const anims = advAnim.getAnimations(elementId);

// Controlar animacion
const anim = advAnim.create(elementId, config);
anim.play();
anim.pause();
anim.reverse();
anim.restart();
anim.seek(0.5);    // Ir al 50%
anim.kill();       // Destruir

// Timeline (secuencia de animaciones)
const timeline = advAnim.createTimeline();
timeline.add(elementId, animation1);
timeline.add(elementId2, animation2, '+=0.2');  // Con offset
timeline.add(elementId3, animation3, '<');      // Al mismo tiempo que anterior
timeline.play();
```

### Stagger Options

```javascript
// Stagger basico
stagger: 0.1   // 0.1s entre cada elemento

// Stagger desde posicion
stagger: {
    each: 0.1,
    from: 'center'  // 'start', 'end', 'center', 'edges', 'random', o indice
}

// Stagger en grid
stagger: {
    each: 0.1,
    grid: [4, 3],   // 4 columnas, 3 filas
    from: 'center',
    axis: 'y'       // Propagar por eje Y
}

// Stagger funcion personalizada
stagger: (index, target, list) => {
    return index * 0.05 + Math.random() * 0.1;
}
```

### Spring Presets

| Preset | Stiffness | Damping | Uso |
|--------|-----------|---------|-----|
| `gentle` | 100 | 14 | Animaciones suaves |
| `wobbly` | 180 | 12 | Efecto rebote |
| `stiff` | 210 | 20 | Movimiento rapido |
| `slow` | 280 | 60 | Movimiento lento |
| `molasses` | 280 | 120 | Muy amortiguado |

```javascript
// Usar preset
advAnim.createSpring(elementId, {
    to: { x: 100 },
    preset: 'wobbly'
});
```

### Motion Path Editor

El editor visual permite:

1. Dibujar trayectorias con Bezier curves
2. Importar paths SVG
3. Previsualizar el movimiento
4. Ajustar velocidad variable a lo largo del path

```javascript
// Crear path desde puntos
const path = advAnim.createPath([
    { x: 0, y: 0 },
    { x: 100, y: 50, curve: 'smooth' },
    { x: 200, y: 0, curve: 'smooth' }
]);

// Aplicar a elemento
advAnim.createMotionPath(elementId, {
    path: path,
    duration: 2
});
```

## Eventos

```javascript
// Animacion iniciada
document.addEventListener('vbp:advanced:animation:start', (e) => {
    console.log('Inicio:', e.detail.elementId);
});

// Animacion completada
document.addEventListener('vbp:advanced:animation:complete', (e) => {
    console.log('Completada:', e.detail.elementId);
});

// Stagger - cada elemento
document.addEventListener('vbp:stagger:element:complete', (e) => {
    console.log('Elemento:', e.detail.index, 'de', e.detail.total);
});

// Gesture
document.addEventListener('vbp:gesture:drag:start', (e) => {
    console.log('Drag iniciado en:', e.detail.elementId);
});

document.addEventListener('vbp:gesture:drag:end', (e) => {
    console.log('Drag finalizado:', e.detail.offset);
});
```

## Timeline Editor

Crea secuencias de animaciones complejas:

```javascript
const tl = advAnim.createTimeline({
    defaults: { duration: 0.5, ease: 'power2.out' }
});

// Agregar animaciones
tl.to('#header', { y: -100 });
tl.to('#content', { opacity: 1 }, '-=0.2');  // Overlap
tl.to('.cards', {
    y: 0,
    stagger: 0.1
}, '<');  // Al mismo tiempo

// Labels para navegacion
tl.addLabel('showCards');
tl.to('.cards', { scale: 1.1 }, 'showCards+=0.5');

// Controlar
tl.play();
tl.pause();
tl.seek('showCards');
tl.reverse();
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+A` | Abrir Animation Builder |
| `Shift+S` | Modo stagger |
| `Shift+M` | Modo motion path |
| `Shift+P` | Modo spring |
| `Space` | Play/Pause preview |

## Integracion con Scroll

Las animaciones avanzadas se pueden vincular al scroll:

```javascript
advAnim.createStagger(parentId, {
    targets: '.card',
    animation: { opacity: [0, 1], y: [30, 0] },
    stagger: { each: 0.1 },
    scrollTrigger: {
        trigger: parentId,
        start: 'top 80%',
        end: 'bottom 20%',
        scrub: true
    }
});
```

## Consideraciones de Performance

### GPU Acceleration

Propiedades optimizadas para GPU:
- `transform` (translate, rotate, scale)
- `opacity`

Evitar animar:
- `width`, `height` (causa layout)
- `top`, `left`, `bottom`, `right`
- `margin`, `padding`

### Force Hardware Acceleration

```javascript
{
    animation: { x: 100 },
    force3D: true  // Fuerza translateZ(0)
}
```

### Performance Tips

1. Limita animaciones simultaneas a < 50 elementos
2. Usa `will-change` con moderacion
3. Desactiva animaciones fuera del viewport
4. Considera reduced motion preferences

```javascript
// Respetar preferencias de usuario
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    advAnim.disableAll();
}
```

## Solucionar Problemas

### Stagger no funciona

1. Verifica que el selector encuentra elementos
2. Comprueba que los hijos existen en el DOM
3. Revisa la estructura del grid si usas grid stagger

### Motion path se ve extraño

1. Verifica que el path SVG es valido
2. Comprueba el `transformOrigin` del elemento
3. Revisa que `autoRotate` esta correctamente configurado

### Spring no se detiene

1. Aumenta el valor de `damping`
2. Verifica que `mass` no es muy alto
3. Considera usar un preset mas amortiguado

### Gestures no responden

1. Verifica que el elemento tiene dimension
2. Comprueba que no hay elementos superpuestos
3. Revisa que los event listeners estan activos
