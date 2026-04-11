# Scroll Animations (Animaciones de Scroll)

Sistema avanzado de animaciones activadas por scroll con soporte para parallax, reveal, pin y progress tracking.

## Descripcion

Las Animaciones de Scroll permiten crear efectos visuales que responden al desplazamiento de la pagina. Incluye animaciones de entrada (reveal), parallax, elementos fijos (pin), y seguimiento de progreso con granularidad fina sobre los triggers y efectos.

## Como Acceder

- Atajo: `Ctrl+Alt+Shift+Y`
- Inspector > Animaciones > Scroll
- Animation Builder > Tab "Scroll"

## Tipos de Animaciones de Scroll

### 1. Reveal (Entrada)

Anima elementos cuando entran en el viewport.

```javascript
{
    type: 'reveal',
    trigger: 'enter',          // 'enter', 'leave', 'enter-back', 'leave-back'
    offset: 20,                // % desde el borde del viewport
    once: true,                // Solo ejecutar una vez
    animation: {
        from: { opacity: 0, y: 50 },
        to: { opacity: 1, y: 0 }
    },
    duration: 0.6,
    easing: 'ease-out'
}
```

### 2. Parallax

Movimiento relativo al scroll para crear profundidad.

```javascript
{
    type: 'parallax',
    speed: 0.5,                // < 1 mas lento, > 1 mas rapido
    direction: 'vertical',     // 'vertical', 'horizontal', 'both'
    range: {
        start: 'top bottom',   // Cuando empieza
        end: 'bottom top'      // Cuando termina
    },
    clamp: true                // Limitar al rango
}
```

### 3. Pin (Fijado)

Mantiene un elemento fijo durante un rango de scroll.

```javascript
{
    type: 'pin',
    start: 'top center',       // Cuando empieza a fijar
    end: '+=500',              // Cuando termina (pixels o %)
    pinSpacing: true,          // Agregar espacio en el documento
    anticipatePin: 1,          // Anticipar el pin (smooth)
    markers: false             // Debug markers
}
```

### 4. Progress (Progreso)

Anima propiedades basado en el progreso del scroll.

```javascript
{
    type: 'progress',
    trigger: {
        start: 'top 80%',
        end: 'bottom 20%'
    },
    properties: {
        opacity: { from: 0, to: 1 },
        scale: { from: 0.8, to: 1 },
        x: { from: -100, to: 0 }
    },
    scrub: true,               // Vinculado al scroll
    scrubSmooth: 1             // Suavizado (0-2)
}
```

## Configuracion

### En el Inspector

1. Selecciona un elemento
2. Ve a Inspector > Animaciones > Scroll
3. Configura:
   - Tipo de animacion
   - Trigger (cuando se activa)
   - Propiedades a animar
   - Duracion y easing
   - Opciones adicionales

### Triggers Disponibles

| Trigger | Descripcion |
|---------|-------------|
| `enter` | Elemento entra en viewport |
| `leave` | Elemento sale del viewport |
| `enter-back` | Elemento re-entra (scroll hacia arriba) |
| `leave-back` | Elemento sale hacia arriba |
| `center` | Elemento cruza el centro |
| `progress` | Basado en posicion relativa |

### Offsets

```javascript
// Porcentaje desde el borde
offset: 20    // 20% desde arriba/abajo

// Trigger strings (GSAP ScrollTrigger syntax)
start: 'top 80%'      // Top del elemento al 80% del viewport
end: 'bottom 20%'     // Bottom del elemento al 20% del viewport

// Valores relativos
end: '+=500'          // 500px despues del start
end: '+=100%'         // 100% de la altura del elemento
```

## API JavaScript

### VBPScrollAnimations

```javascript
const scrollAnim = window.VBPScrollAnimations;

// Crear animacion de scroll
scrollAnim.create(elementId, {
    type: 'reveal',
    trigger: 'enter',
    offset: 20,
    animation: {
        from: { opacity: 0, y: 30 },
        to: { opacity: 1, y: 0 }
    },
    duration: 0.6
});

// Crear parallax
scrollAnim.createParallax(elementId, {
    speed: 0.5,
    direction: 'vertical'
});

// Crear pin
scrollAnim.createPin(elementId, {
    start: 'top center',
    end: '+=300'
});

// Crear progress tracking
scrollAnim.createProgress(elementId, {
    trigger: { start: 'top 80%', end: 'bottom 20%' },
    properties: { opacity: { from: 0, to: 1 } },
    scrub: true
});

// Obtener animaciones de un elemento
const animations = scrollAnim.getAnimations(elementId);

// Actualizar animacion
scrollAnim.update(animationId, { offset: 30 });

// Eliminar animacion
scrollAnim.remove(animationId);

// Eliminar todas de un elemento
scrollAnim.removeAll(elementId);

// Refrescar (despues de cambios de layout)
scrollAnim.refresh();

// Pausar/reanudar todas
scrollAnim.pauseAll();
scrollAnim.resumeAll();

// Debug mode
scrollAnim.enableMarkers(true);
```

### Store de Alpine

```javascript
const store = Alpine.store('vbp');

// Acceder a configuracion de scroll
store.animations.scroll.enabled = true;
store.animations.scroll.defaultOffset = 20;
store.animations.scroll.defaultEasing = 'ease-out';

// Obtener elementos con scroll animation
store.animations.scroll.getAnimatedElements();
```

### Eventos

```javascript
// Animacion iniciada
document.addEventListener('vbp:scroll:animation:start', (e) => {
    console.log('Iniciando:', e.detail.elementId);
    console.log('Direccion:', e.detail.direction);
});

// Animacion completada
document.addEventListener('vbp:scroll:animation:complete', (e) => {
    console.log('Completada:', e.detail.elementId);
});

// Progreso actualizado
document.addEventListener('vbp:scroll:progress', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Progreso:', e.detail.progress);  // 0-1
});

// Pin activado/desactivado
document.addEventListener('vbp:scroll:pin:toggle', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Pinned:', e.detail.isPinned);
});
```

## Presets de Animacion

### Reveal Presets

| Preset | Descripcion |
|--------|-------------|
| `fadeIn` | Aparece con opacity |
| `fadeInUp` | Aparece subiendo |
| `fadeInDown` | Aparece bajando |
| `fadeInLeft` | Aparece desde izquierda |
| `fadeInRight` | Aparece desde derecha |
| `zoomIn` | Aparece creciendo |
| `slideInUp` | Desliza hacia arriba |
| `rotateIn` | Aparece rotando |

### Aplicar Preset

```javascript
scrollAnim.applyPreset(elementId, 'fadeInUp', {
    offset: 30,
    duration: 0.8,
    once: true
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+Shift+Y` | Abrir editor de scroll |
| `Ctrl+Alt+M` | Toggle markers (debug) |

## Integracion con Animation Builder

Las animaciones de scroll se integran con el Animation Builder:

1. Crea keyframes en Animation Builder
2. En "Trigger", selecciona "Scroll"
3. Configura el trigger (enter, progress, etc.)
4. La animacion se activara por scroll

## Consideraciones de Performance

### Optimizaciones Automaticas

- Uso de `will-change` controlado
- IntersectionObserver para triggers
- RequestAnimationFrame para animaciones
- Lazy evaluation fuera del viewport

### Recomendaciones

1. **Limita elementos animados**: Demasiadas animaciones afectan performance
2. **Usa transform y opacity**: Evita propiedades que causan layout
3. **Activa `once`**: Si la animacion no necesita repetirse
4. **Evita anidamiento profundo**: Simplifica la estructura

### Monitor de Performance

```javascript
// Activar monitor
const monitor = window.VBPPerformanceMonitor;
monitor.trackScrollAnimations(true);

// Ver metricas
const metrics = monitor.getScrollMetrics();
console.log('FPS:', metrics.fps);
console.log('Elementos activos:', metrics.activeElements);
```

## Debug Mode

Activa markers visuales para depurar:

```javascript
// Activar markers globalmente
scrollAnim.enableMarkers(true);

// Por animacion
scrollAnim.create(elementId, {
    ...config,
    markers: true  // Solo esta animacion
});
```

Los markers muestran:
- Linea de start (verde)
- Linea de end (roja)
- Progreso actual
- Estado del pin

## Solucionar Problemas

### La animacion no se activa

1. Verifica que el elemento esta en el DOM
2. Comprueba que el offset es correcto
3. Revisa que no hay `overflow: hidden` en ancestros
4. Activa markers para debug

### Performance lenta

1. Reduce el numero de animaciones
2. Usa propiedades optimizadas (transform, opacity)
3. Activa `once` si es posible
4. Simplifica la estructura del DOM

### El parallax se ve irregular

1. Verifica que `scrubSmooth` es adecuado
2. Comprueba el valor de `speed`
3. Asegurate de que no hay conflictos con CSS

### El pin no funciona bien

1. Verifica que el elemento tiene altura definida
2. Comprueba que `pinSpacing` esta configurado
3. Revisa conflictos con `position: fixed` en ancestros
