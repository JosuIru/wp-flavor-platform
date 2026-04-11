# VBP Micro-interactions Checklist

Guia de micro-interacciones para el Visual Builder Pro. Cada interaccion debe sentirse natural, responsiva y proporcionar feedback claro.

---

## Principios Generales

### Timing
| Tipo | Duracion | Uso |
|------|----------|-----|
| Instantaneo | 0-50ms | Hover, active states |
| Rapido | 100-200ms | Feedback, transiciones pequenas |
| Normal | 200-300ms | Modales, paneles |
| Deliberado | 300-500ms | Animaciones de entrada complejas |

### Easing
| Funcion | CSS | Uso |
|---------|-----|-----|
| ease-out | `cubic-bezier(0, 0, 0.2, 1)` | Entradas, apariciones |
| ease-in | `cubic-bezier(0.4, 0, 1, 1)` | Salidas, desapariciones |
| ease-in-out | `cubic-bezier(0.4, 0, 0.2, 1)` | Movimientos, cambios de estado |
| spring | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Bounce sutil, drops |

---

## Buttons

### Estados
- [ ] **Idle** - Estado base, sin interaccion
- [ ] **Hover** - scale(1.02), sombra aumenta ligeramente
- [ ] **Active/Pressed** - scale(0.98), sombra disminuye
- [ ] **Focus** - ring visible (2px outline offset)
- [ ] **Loading** - spinner interno + texto "Guardando..."
- [ ] **Success** - checkmark animado + color verde temporal (2s)
- [ ] **Error** - shake horizontal (3 ciclos) + color rojo

### CSS de Referencia
```css
.vbp-button {
  transition: transform 100ms ease-out,
              box-shadow 100ms ease-out,
              background-color 150ms ease-out;
}

.vbp-button:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.vbp-button:active {
  transform: scale(0.98);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.vbp-button:focus-visible {
  outline: 2px solid var(--vbp-primary);
  outline-offset: 2px;
}

.vbp-button--loading {
  pointer-events: none;
  opacity: 0.8;
}

.vbp-button--success {
  background-color: var(--vbp-success);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}

.vbp-button--error {
  animation: shake 200ms ease-in-out 3;
  background-color: var(--vbp-error);
}
```

---

## Drag & Drop

### Fases de la Interaccion
- [ ] **Idle** - Cursor grab, elemento en posicion normal
- [ ] **Hover over draggable** - Cursor grab, sutil highlight
- [ ] **Pickup (mousedown + move)** - Elemento eleva (shadow aumenta), cursor grabbing
- [ ] **Dragging** - Ghost semi-transparente (opacity 0.7) sigue cursor
- [ ] **Over valid target** - Target se ilumina (border highlight), expande ligeramente
- [ ] **Over invalid target** - Cursor not-allowed, sin highlight
- [ ] **Drop** - Animacion de "settle" (spring easing), ghost desaparece
- [ ] **Cancel (Escape)** - Elemento regresa a posicion original con animacion

### CSS de Referencia
```css
.vbp-draggable {
  cursor: grab;
  transition: transform 150ms ease-out,
              box-shadow 150ms ease-out;
}

.vbp-draggable:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.vbp-draggable--dragging {
  cursor: grabbing;
  opacity: 0.7;
  transform: scale(1.02) rotate(2deg);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
  z-index: 1000;
}

.vbp-drop-zone {
  transition: border-color 100ms ease-out,
              background-color 100ms ease-out,
              transform 150ms ease-out;
}

.vbp-drop-zone--active {
  border-color: var(--vbp-primary);
  background-color: var(--vbp-primary-light);
  transform: scale(1.01);
}

.vbp-drop-zone--invalid {
  border-color: var(--vbp-error);
  opacity: 0.5;
}

@keyframes settle {
  0% { transform: scale(1.05); }
  50% { transform: scale(0.98); }
  100% { transform: scale(1); }
}

.vbp-draggable--dropped {
  animation: settle 300ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
```

### Indicadores de Drop
```css
/* Linea indicadora de posicion */
.vbp-drop-indicator {
  height: 2px;
  background: var(--vbp-primary);
  border-radius: 1px;
  animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
```

---

## Forms

### Input Text
- [ ] **Idle** - Borde gris claro, fondo blanco
- [ ] **Focus** - Borde cambia a primario, label se mueve arriba (floating label)
- [ ] **Typing** - Validacion en tiempo real despues de 300ms debounce
- [ ] **Valid** - Borde verde, checkmark sutil al final
- [ ] **Error** - Borde rojo, mensaje de error debajo con slide-in
- [ ] **Disabled** - Fondo gris claro, cursor not-allowed

### CSS de Referencia
```css
.vbp-input {
  border: 1px solid var(--vbp-border);
  border-radius: var(--vbp-radius-sm);
  padding: 12px 16px;
  transition: border-color 150ms ease-out,
              box-shadow 150ms ease-out;
}

.vbp-input:focus {
  border-color: var(--vbp-primary);
  box-shadow: 0 0 0 3px var(--vbp-primary-light);
  outline: none;
}

.vbp-input--valid {
  border-color: var(--vbp-success);
}

.vbp-input--error {
  border-color: var(--vbp-error);
}

.vbp-input:disabled {
  background-color: var(--vbp-bg-disabled);
  cursor: not-allowed;
  opacity: 0.6;
}

/* Floating Label */
.vbp-label {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  transition: all 150ms ease-out;
  pointer-events: none;
  color: var(--vbp-text-muted);
}

.vbp-input:focus + .vbp-label,
.vbp-input:not(:placeholder-shown) + .vbp-label {
  top: 0;
  transform: translateY(-50%) scale(0.85);
  background: white;
  padding: 0 4px;
  color: var(--vbp-primary);
}

/* Error Message */
.vbp-error-message {
  color: var(--vbp-error);
  font-size: 12px;
  margin-top: 4px;
  animation: slideIn 150ms ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### Select / Dropdown
- [ ] **Closed** - Chevron apunta abajo
- [ ] **Opening** - Dropdown aparece con scale + fade desde origen
- [ ] **Open** - Chevron rota 180 grados
- [ ] **Hover option** - Background highlight
- [ ] **Select option** - Checkmark aparece, dropdown cierra
- [ ] **Closing** - Fade out rapido

---

## Panels

### Sidebar Panel
- [ ] **Opening** - Slide desde el lado + fade, 200ms
- [ ] **Open** - Posicion final, contenido visible
- [ ] **Closing** - Fade + slide hacia fuera, 150ms (mas rapido)
- [ ] **Resize** - Smooth, sin jank, cursor col-resize

### CSS de Referencia
```css
.vbp-panel {
  transform: translateX(100%);
  opacity: 0;
  transition: transform 200ms ease-out,
              opacity 200ms ease-out;
}

.vbp-panel--open {
  transform: translateX(0);
  opacity: 1;
}

.vbp-panel--closing {
  transform: translateX(100%);
  opacity: 0;
  transition-duration: 150ms;
}

/* Resize Handle */
.vbp-panel__resize-handle {
  width: 4px;
  cursor: col-resize;
  background: transparent;
  transition: background-color 100ms ease-out;
}

.vbp-panel__resize-handle:hover,
.vbp-panel__resize-handle--active {
  background: var(--vbp-primary);
}
```

### Modal
- [ ] **Opening** - Backdrop fade in + modal scale from 0.95
- [ ] **Open** - Centrado, focus trapped
- [ ] **Closing** - Scale down + fade out, 150ms

```css
.vbp-modal-backdrop {
  background: rgba(0, 0, 0, 0);
  transition: background-color 200ms ease-out;
}

.vbp-modal-backdrop--open {
  background: rgba(0, 0, 0, 0.5);
}

.vbp-modal {
  transform: scale(0.95);
  opacity: 0;
  transition: transform 200ms ease-out,
              opacity 200ms ease-out;
}

.vbp-modal--open {
  transform: scale(1);
  opacity: 1;
}
```

---

## Selection

### Single Selection
- [ ] **Select** - Bounding box aparece con handles en esquinas
- [ ] **Deselect** - Click fuera, bounding box desaparece con fade

### Multi-select
- [ ] **Shift+click** - Agrega elemento a seleccion existente
- [ ] **Ctrl/Cmd+click** - Toggle elemento individual
- [ ] **Drag select (marquee)** - Rectangulo de seleccion mientras se arrastra

### CSS de Referencia
```css
.vbp-selected {
  outline: 2px solid var(--vbp-primary);
  outline-offset: 2px;
}

.vbp-selection-box {
  position: absolute;
  border: 2px solid var(--vbp-primary);
  background: var(--vbp-primary-light);
  pointer-events: none;
}

/* Resize Handles */
.vbp-resize-handle {
  width: 8px;
  height: 8px;
  background: white;
  border: 2px solid var(--vbp-primary);
  border-radius: 50%;
  transition: transform 100ms ease-out;
}

.vbp-resize-handle:hover {
  transform: scale(1.2);
}
```

---

## Loading States

### Skeleton
- [ ] **Shimmer animation** - Gradiente que se mueve de izquierda a derecha

```css
.vbp-skeleton {
  background: linear-gradient(
    90deg,
    var(--vbp-bg-skeleton) 0%,
    var(--vbp-bg-skeleton-highlight) 50%,
    var(--vbp-bg-skeleton) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: var(--vbp-radius-sm);
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

### Spinner
- [ ] **Rotacion suave** - 360 grados continuo

```css
.vbp-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--vbp-border);
  border-top-color: var(--vbp-primary);
  border-radius: 50%;
  animation: spin 600ms linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
```

### Progress Bar
- [ ] **Indeterminate** - Barra que se mueve de lado a lado
- [ ] **Determinate** - Barra que crece segun porcentaje

```css
.vbp-progress {
  height: 4px;
  background: var(--vbp-bg-secondary);
  border-radius: 2px;
  overflow: hidden;
}

.vbp-progress__bar {
  height: 100%;
  background: var(--vbp-primary);
  transition: width 300ms ease-out;
}

/* Indeterminate */
.vbp-progress--indeterminate .vbp-progress__bar {
  width: 30%;
  animation: indeterminate 1.5s infinite ease-in-out;
}

@keyframes indeterminate {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(400%); }
}
```

---

## Notifications / Toasts

### Aparicion
- [ ] **Slide in** - Desde arriba derecha con fade
- [ ] **Stack** - Nuevas notificaciones empujan las anteriores

### Auto-dismiss
- [ ] **Progress bar** - Barra que disminuye mostrando tiempo restante
- [ ] **Hover pause** - Hover pausa el auto-dismiss

### Dismiss Manual
- [ ] **X button** - Hover revela boton de cerrar
- [ ] **Swipe** - En mobile, swipe derecha para dismiss

```css
.vbp-toast {
  transform: translateX(100%);
  opacity: 0;
  transition: transform 300ms ease-out,
              opacity 300ms ease-out;
}

.vbp-toast--visible {
  transform: translateX(0);
  opacity: 1;
}

.vbp-toast--dismissing {
  transform: translateX(100%);
  opacity: 0;
  transition-duration: 200ms;
}

.vbp-toast__progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: var(--vbp-primary);
  animation: countdown var(--toast-duration) linear;
}

@keyframes countdown {
  from { width: 100%; }
  to { width: 0%; }
}

.vbp-toast:hover .vbp-toast__progress {
  animation-play-state: paused;
}
```

---

## Tooltips

### Comportamiento
- [ ] **Delay de aparicion** - 300ms antes de mostrar
- [ ] **Aparicion** - Fade in + slight scale
- [ ] **Desaparicion** - Inmediata al salir del elemento

```css
.vbp-tooltip {
  opacity: 0;
  transform: scale(0.95);
  transition: opacity 150ms ease-out,
              transform 150ms ease-out;
  pointer-events: none;
}

.vbp-tooltip--visible {
  opacity: 1;
  transform: scale(1);
}

/* Arrow */
.vbp-tooltip::after {
  content: '';
  position: absolute;
  border: 6px solid transparent;
}

.vbp-tooltip--top::after {
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%);
  border-top-color: var(--vbp-bg-tooltip);
}
```

---

## Transiciones de Pagina/Vista

### Cambio de Tab
- [ ] **Tab active** - Underline/indicator se desliza suavemente
- [ ] **Content** - Crossfade entre contenidos

```css
.vbp-tabs__indicator {
  position: absolute;
  bottom: 0;
  height: 2px;
  background: var(--vbp-primary);
  transition: left 200ms ease-out,
              width 200ms ease-out;
}

.vbp-tab-content {
  opacity: 0;
  transform: translateY(8px);
  transition: opacity 150ms ease-out,
              transform 150ms ease-out;
}

.vbp-tab-content--active {
  opacity: 1;
  transform: translateY(0);
}
```

### Cambio de Breakpoint
- [ ] **Transicion suave** - Layout cambia con animacion
- [ ] **Indicador** - Mostrar breakpoint actual brevemente

---

## Comandos y Atajos

### Command Palette (Ctrl+K)
- [ ] **Aparicion** - Fade in + scale, input auto-focused
- [ ] **Busqueda** - Resultados filtran en tiempo real
- [ ] **Navegacion** - Arrow keys para navegar, Enter para seleccionar
- [ ] **Cierre** - Escape o click fuera

```css
.vbp-command-palette {
  transform: scale(0.95) translateY(-10px);
  opacity: 0;
  transition: transform 200ms ease-out,
              opacity 200ms ease-out;
}

.vbp-command-palette--open {
  transform: scale(1) translateY(0);
  opacity: 1;
}

.vbp-command-item {
  transition: background-color 50ms ease-out;
}

.vbp-command-item--highlighted {
  background: var(--vbp-bg-highlight);
}
```

---

## Checklist de Implementacion

### Prioridad Alta
- [ ] Button hover/active states
- [ ] Drag & drop feedback completo
- [ ] Input focus states
- [ ] Loading spinners
- [ ] Toast notifications

### Prioridad Media
- [ ] Panel transitions
- [ ] Selection visual feedback
- [ ] Skeleton loaders
- [ ] Tooltips con delay

### Prioridad Baja
- [ ] Command palette animations
- [ ] Tab indicator slide
- [ ] Progress bars
- [ ] Confetti on publish (opcional)

---

## Testing de Micro-interacciones

### Manual
1. Grabar sesion con Screen Recording
2. Reproducir a 0.25x velocidad
3. Verificar que cada transicion es suave
4. Verificar timing apropiado

### Automatizado
```javascript
// Verificar duracion de transiciones
const button = document.querySelector('.vbp-button');
const styles = getComputedStyle(button);
const transitionDuration = parseFloat(styles.transitionDuration) * 1000;

console.assert(
  transitionDuration >= 100 && transitionDuration <= 300,
  `Transition duration should be 100-300ms, got ${transitionDuration}ms`
);
```

---

## Recursos

- [Material Design Motion](https://material.io/design/motion/)
- [Framer Motion](https://www.framer.com/motion/)
- [CSS Easing Functions](https://easings.net/)
- [The UX of Motion](https://medium.com/ux-in-motion)
