# VBP UX Audit Checklist

Checklist completo para auditar la experiencia de usuario del Visual Builder Pro.

## Resumen Ejecutivo

| Categoria | Items | Peso |
|-----------|-------|------|
| Respuesta y Velocidad | 10 | 25% |
| Predictibilidad | 9 | 20% |
| Claridad Visual | 8 | 15% |
| Manejo de Errores | 6 | 15% |
| Eficiencia | 6 | 15% |
| Accesibilidad | 6 | 10% |
| **Total** | **45** | **100%** |

---

## 1. Respuesta y Velocidad

### 1.1 Tiempos de Respuesta
- [ ] **Clicks responden en < 100ms** - Toda accion de click debe dar feedback inmediato
- [ ] **Drag inicia en < 50ms** - Sin delay al empezar a arrastrar
- [ ] **Typing sin lag** - Texto aparece instantaneamente (< 16ms por frame)
- [ ] **Hover states en < 50ms** - Estados hover son inmediatos
- [ ] **Transiciones <= 300ms** - Animaciones no frenan el flujo de trabajo
- [ ] **Guardado en < 2s** - Feedback de guardado rapido con indicador

### 1.2 Feedback Visual
- [ ] **Loading states claros** - Spinners/skeletons cuando algo carga
- [ ] **Progress indicators** - Para operaciones largas (>1s)
- [ ] **Confirmaciones visibles** - Toast/notificacion al completar accion
- [ ] **Estados de error claros** - Mensaje especifico, no generico

### Metricas Objetivo
| Metrica | Target | Critico |
|---------|--------|---------|
| First Click Response | < 100ms | < 200ms |
| Drag Start | < 50ms | < 100ms |
| Input Latency | < 16ms | < 50ms |
| Transition Duration | 150-300ms | < 500ms |
| Save Operation | < 2s | < 5s |

---

## 2. Predictibilidad

### 2.1 Comportamiento Consistente
- [ ] **Mismo click = mismo resultado** - Sin sorpresas en la interaccion
- [ ] **Undo siempre funciona** - Ctrl+Z deshace la ultima accion correctamente
- [ ] **Redo siempre funciona** - Ctrl+Y/Shift+Ctrl+Z rehace la accion
- [ ] **Escape cancela** - Escape cierra modales, cancela operaciones en curso
- [ ] **Enter confirma** - Enter acepta/confirma en dialogos

### 2.2 Convenciones Respetadas
- [ ] **Atajos estandar** - Ctrl+S guarda, Ctrl+C copia, Ctrl+V pega, etc.
- [ ] **Cursores correctos** - Pointer en clickeables, move en draggables, text en inputs
- [ ] **Scroll natural** - Direccion de scroll esperada en todas las plataformas
- [ ] **Zoom con rueda** - Ctrl+Wheel hace zoom cuando aplica

### Atajos Esperados
| Atajo | Accion | Estado |
|-------|--------|--------|
| Ctrl+S | Guardar | [ ] |
| Ctrl+Z | Deshacer | [ ] |
| Ctrl+Y / Ctrl+Shift+Z | Rehacer | [ ] |
| Ctrl+C | Copiar | [ ] |
| Ctrl+V | Pegar | [ ] |
| Ctrl+D | Duplicar | [ ] |
| Delete / Backspace | Eliminar | [ ] |
| Escape | Cancelar/Cerrar | [ ] |
| Enter | Confirmar | [ ] |
| Ctrl+A | Seleccionar todo | [ ] |

---

## 3. Claridad Visual

### 3.1 Jerarquia
- [ ] **Accion principal obvia** - Boton primario destacado visualmente
- [ ] **Agrupacion logica** - Controles relacionados estan juntos
- [ ] **Espaciado consistente** - Mismo spacing entre elementos similares
- [ ] **Contraste suficiente** - Texto legible (4.5:1 minimo WCAG AA)

### 3.2 Estados
- [ ] **Selected state visible** - Elemento seleccionado claramente marcado
- [ ] **Hover state visible** - Feedback al pasar el mouse
- [ ] **Disabled state claro** - Elementos deshabilitados se ven diferentes (opacity 0.5)
- [ ] **Focus visible** - Focus de teclado siempre visible (outline/ring)

### Colores de Estado
| Estado | Color Base | Uso |
|--------|------------|-----|
| Primary | Blue 600 | Acciones principales |
| Success | Green 600 | Confirmaciones, exito |
| Warning | Amber 500 | Advertencias |
| Error | Red 600 | Errores, destructivo |
| Disabled | Gray 400 | Inactivo |
| Selected | Blue 100 bg + Blue 600 border | Seleccion |

---

## 4. Manejo de Errores

### 4.1 Prevencion
- [ ] **Confirmacion para destructivos** - "Eliminar?" antes de borrar con detalle
- [ ] **Validacion en tiempo real** - Errores mostrados mientras se escribe
- [ ] **Limites claros** - Mostrar limites antes de alcanzarlos (caracteres, tamano, etc.)

### 4.2 Recuperacion
- [ ] **Mensajes de error utiles** - Que paso + como solucionarlo
- [ ] **Retry disponible** - Boton para reintentar operaciones fallidas
- [ ] **No perder trabajo** - Autosave cada 30s, recovery de datos al recargar

### Formato de Errores
```
[Icono Error] Titulo del Error
Descripcion de que paso y por que.
[Accion: Reintentar] [Accion: Cerrar]
```

---

## 5. Eficiencia

### 5.1 Atajos
- [ ] **Atajos para acciones comunes** - Duplicar, eliminar, guardar accesibles por teclado
- [ ] **Command palette** - Cmd+K/Ctrl+K para buscar comandos rapidamente
- [ ] **Historial accesible** - Undo history visible y navegable

### 5.2 Flujos Optimizados
- [ ] **Minimos clicks para tareas comunes** - Agregar elemento: <=3 clicks
- [ ] **Drag & drop donde tiene sentido** - Reordenar, mover, agregar elementos
- [ ] **Bulk operations** - Seleccionar multiples elementos, editar juntos

### Conteo de Clicks
| Tarea | Target | Maximo |
|-------|--------|--------|
| Agregar bloque | 2 clicks | 3 clicks |
| Cambiar texto | 1 click + typing | 2 clicks |
| Duplicar elemento | 1 click o Ctrl+D | 2 clicks |
| Eliminar elemento | 1 click o Delete | 2 clicks |
| Guardar cambios | Ctrl+S | 1 click |
| Deshacer | Ctrl+Z | 1 click |

---

## 6. Onboarding y Ayuda

### 6.1 Descubribilidad
- [ ] **Tooltips informativos** - Hover muestra que hace cada elemento
- [ ] **Placeholder text util** - Guia en campos vacios con ejemplos
- [ ] **Empty states guian** - Que hacer cuando no hay contenido

### 6.2 Documentacion
- [ ] **Ayuda contextual** - ? abre ayuda relevante al contexto actual
- [ ] **Atajos visibles** - Mostrados en tooltips y menus
- [ ] **Tour inicial** - Guia interactiva para nuevos usuarios

### Formato de Tooltips
```
Titulo de la Accion
Descripcion breve de que hace.
Atajo: Ctrl+X
```

---

## 7. Accesibilidad

### 7.1 Teclado
- [ ] **Todo accesible por teclado** - Sin dependencia del mouse
- [ ] **Tab order logico** - Navegacion en orden visual de arriba a abajo, izquierda a derecha
- [ ] **Focus trap en modales** - Tab no sale del modal abierto

### 7.2 Screen Readers
- [ ] **Labels en controles** - Todos los inputs tienen label asociado
- [ ] **ARIA donde necesario** - Roles y estados correctos para widgets custom
- [ ] **Anuncios de cambios** - Live regions para updates dinamicos

### ARIA Checklist
| Elemento | Atributo Requerido |
|----------|-------------------|
| Botones iconicos | aria-label |
| Tabs | role="tablist", aria-selected |
| Modales | role="dialog", aria-modal |
| Alerts | role="alert", aria-live |
| Progress | role="progressbar", aria-valuenow |
| Menus | role="menu", aria-expanded |

---

## 8. Consistencia

### 8.1 Visual
- [ ] **Mismos colores para mismos significados** - Primario, error, success consistentes
- [ ] **Misma tipografia** - Fuentes consistentes en todo el editor
- [ ] **Mismos iconos** - Iconografia coherente del mismo set

### 8.2 Comportamiento
- [ ] **Mismos patrones** - Formularios, modales, etc. funcionan igual siempre
- [ ] **Misma terminologia** - Mismo nombre para misma cosa en toda la UI

### Tokens de Diseno
| Token | Valor | Uso |
|-------|-------|-----|
| `--vbp-font-family` | Inter, system-ui | Texto UI |
| `--vbp-radius-sm` | 4px | Inputs, botones pequenos |
| `--vbp-radius-md` | 8px | Cards, paneles |
| `--vbp-radius-lg` | 12px | Modales |
| `--vbp-shadow-sm` | 0 1px 2px rgba(0,0,0,0.05) | Elevacion sutil |
| `--vbp-shadow-md` | 0 4px 6px rgba(0,0,0,0.1) | Cards, dropdowns |
| `--vbp-shadow-lg` | 0 10px 15px rgba(0,0,0,0.1) | Modales |
| `--vbp-transition` | 150ms ease-out | Animaciones rapidas |

---

## Proceso de Auditoria

### Paso 1: Preparacion
1. Abrir VBP en navegador limpio (incognito)
2. Tener cronometro/DevTools Performance ready
3. Preparar documento para notas

### Paso 2: Auditoria Automatizada
```bash
# Ejecutar script de auditoria
cd /path/to/plugin
node tools/ux-audit.js --url="http://sitio.local/wp-admin"
```

### Paso 3: Auditoria Manual
1. Ir item por item del checklist
2. Marcar como:
   - [x] Pasa
   - [ ] Falla
   - [~] Parcial (agregar nota)

### Paso 4: Documentar Resultados
```markdown
## Resultados Auditoria [FECHA]

### Score: XX/45 (XX%)

### Items Fallidos
1. Item - Descripcion del problema - Severidad (Alta/Media/Baja)

### Items Parciales
1. Item - Que funciona, que no - Nota

### Recomendaciones
1. Prioridad Alta: ...
2. Prioridad Media: ...
3. Prioridad Baja: ...
```

---

## Severidad de Issues

| Severidad | Criterio | Ejemplo |
|-----------|----------|---------|
| **Critica** | Bloquea flujo principal | No se puede guardar |
| **Alta** | Afecta productividad | Click tarda 500ms |
| **Media** | Molestia frecuente | Tooltip no informativo |
| **Baja** | Mejora nice-to-have | Animacion podria ser mas suave |

---

## Herramientas Recomendadas

### Medicion de Performance
- Chrome DevTools > Performance
- Lighthouse
- WebPageTest

### Accesibilidad
- axe DevTools
- WAVE
- Chrome DevTools > Accessibility

### Contraste
- WebAIM Contrast Checker
- Stark (Figma plugin)

---

## Referencias

- [Nielsen Norman Group - Response Time Limits](https://www.nngroup.com/articles/response-times-3-important-limits/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Material Design - Motion](https://material.io/design/motion/)
- [Apple HIG - Responsiveness](https://developer.apple.com/design/human-interface-guidelines/)
