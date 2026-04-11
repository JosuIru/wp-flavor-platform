# Plan de Mejoras VBP - Implementación Paralela

## Resumen Ejecutivo

- **Total mejoras**: 15
- **Fases**: 3
- **Agentes máximos en paralelo**: 5
- **Tiempo estimado total**: ~3-4 horas (ejecutando en paralelo)

---

## Fase 1: Quick Wins (5 agentes en paralelo)

**Dependencias**: Ninguna
**Tiempo**: ~30-45 min

| Agente | Mejora | Archivos a crear/modificar |
|--------|--------|---------------------------|
| A1 | **Smart Guides** | `vbp-smart-guides.js`, `vbp-smart-guides.css` |
| A2 | **Spacing Indicators** | `vbp-spacing-indicators.js`, `vbp-spacing-indicators.css` |
| A3 | **Copy/Paste Styles** | Modificar `vbp-keyboard-clipboard.js` |
| A4 | **Zoom to Selection** | Modificar `vbp-canvas.js`, agregar método |
| A5 | **Bulk Property Edit** | `vbp-bulk-edit.js`, modificar `vbp-inspector.js` |

### Detalle por agente:

#### A1: Smart Guides
```
- Detectar elementos cercanos al arrastrar
- Mostrar líneas de alineación (center, edges)
- Snap automático a 8px grid y a otros elementos
- Líneas: rojo para center, azul para edges
```

#### A2: Spacing Indicators
```
- Mostrar distancia en px al hacer hover entre elementos
- Flechas bidireccionales con valor numérico
- Activar con Alt+hover o automático al arrastrar
- Colores: naranja para spacing
```

#### A3: Copy/Paste Styles
```
- Ctrl+Alt+C: Copiar estilos del elemento seleccionado
- Ctrl+Alt+V: Pegar estilos a selección actual
- Almacenar en clipboard interno: colors, typography, spacing, borders
- Notificación toast de confirmación
```

#### A4: Zoom to Selection
```
- Atajo: Ctrl+2 o doble-click en minimap
- Calcular bounding box de selección
- Aplicar zoom y pan para centrar
- Animación suave de transición
```

#### A5: Bulk Property Edit
```
- Selección múltiple + inspector muestra campos comunes
- Campos con valores mixtos muestran placeholder "Mixed"
- Editar aplica a todos los seleccionados
- Checkbox para "aplicar solo a seleccionados"
```

---

## Fase 2: Medium Effort (5 agentes en paralelo)

**Dependencias**: Ninguna (puede correr después o durante Fase 1)
**Tiempo**: ~1-2 horas

| Agente | Mejora | Archivos a crear/modificar |
|--------|--------|---------------------------|
| A6 | **Constraints/Pinning** | `vbp-constraints.js`, modificar inspector |
| A7 | **Animation Builder** | `vbp-animation-builder.js`, `vbp-animation-builder.css`, panel nuevo |
| A8 | **Asset Manager** | `vbp-asset-manager.js`, `vbp-asset-manager.css`, panel nuevo |
| A9 | **Global Styles** | `vbp-global-styles.js`, clase PHP, API REST |
| A10 | **Symbol Import/Export** | Modificar `vbp-symbols.js`, agregar endpoints |

### Detalle por agente:

#### A6: Constraints/Pinning
```
- Panel en inspector: Top, Right, Bottom, Left, Center H/V
- Checkboxes para anclar a cada borde
- Al redimensionar padre, hijos anclados mantienen distancia
- Presets: Fill, Center, Top-Left, etc.
- Visualización en canvas de constraints activos
```

#### A7: Animation Builder
```
- Panel de timeline con keyframes
- Propiedades animables: transform, opacity, colors
- Curvas de easing predefinidas + custom bezier
- Preview en canvas
- Exportar como CSS @keyframes
- Triggers: scroll, hover, click, load
```

#### A8: Asset Manager
```
- Panel lateral con librería de medios
- Tabs: Imágenes, SVGs, Videos, Iconos
- Drag & drop para subir
- Búsqueda y filtros
- Integración con Unsplash existente
- Favoritos y carpetas
```

#### A9: Global Styles
```
- Definir estilos globales: .heading-1, .button-primary, etc.
- Aplicar a elementos desde inspector
- Editar estilo global actualiza todas las instancias
- Panel de gestión de estilos
- Exportar como CSS
```

#### A10: Symbol Import/Export
```
- Exportar símbolo como JSON
- Importar símbolo desde JSON
- Incluir variantes y metadata
- Validación de estructura
- Merge con símbolos existentes
```

---

## Fase 3: High Effort (5 agentes, algunos secuenciales)

**Dependencias**: Algunas con Fase 2
**Tiempo**: ~2-3 horas

| Agente | Mejora | Dependencias |
|--------|--------|--------------|
| A11 | **Real-time Collab** | Colaboración existente |
| A12 | **Prototype Mode** | Animation Builder (A7) |
| A13 | **Responsive Variants** | Constraints (A6) |
| A14 | **AI Layout Assist** | Ninguna |
| A15 | **Branching/Versions** | Version History existente |

### Detalle por agente:

#### A11: Real-time Collab (WebSockets)
```
- Servidor WebSocket (Node.js o PHP Ratchet)
- Cursores de otros usuarios en tiempo real
- Locks optimistas por elemento
- Sincronización de cambios con CRDT/OT
- Indicador de quién está editando qué
- Fallback a Heartbeat si WS falla
```

#### A12: Prototype Mode
```
- Definir interacciones: click, hover, scroll
- Acciones: navegar a frame, mostrar overlay, animar
- Conectar frames con flechas
- Modo preview interactivo
- Exportar como HTML interactivo
- Smart animate entre estados
```

#### A13: Responsive Variants
```
- Guardar variante de layout por breakpoint
- Switch de breakpoint mantiene estructura, cambia layout
- Override de propiedades por breakpoint
- Visualización de diferencias entre breakpoints
- Copiar layout de un breakpoint a otro
```

#### A14: AI Layout Assist
```
- Analizar contenido y sugerir layouts
- "Crear hero section" -> genera estructura
- Ajustar spacing automáticamente
- Sugerir colores complementarios
- Generar variantes de diseño
- Integración con API de IA existente
```

#### A15: Branching/Versions
```
- Crear branch de diseño
- Merge branches con resolución de conflictos
- Diff visual entre branches
- Nombrar y describir branches
- Restaurar desde cualquier punto
- Colaboración por branch
```

---

## Diagrama de Ejecución

```
Tiempo →

Fase 1 (Quick Wins):
├─ A1: Smart Guides ─────────┐
├─ A2: Spacing Indicators ───┤
├─ A3: Copy/Paste Styles ────┼──→ DONE
├─ A4: Zoom to Selection ────┤
└─ A5: Bulk Property Edit ───┘

Fase 2 (Medium):
├─ A6: Constraints ──────────────────┐
├─ A7: Animation Builder ────────────┼──→ DONE
├─ A8: Asset Manager ────────────────┤
├─ A9: Global Styles ────────────────┤
└─ A10: Symbol Import/Export ────────┘

Fase 3 (High Effort):
├─ A11: Real-time Collab ────────────────────┐
├─ A12: Prototype Mode ──────[espera A7]─────┼──→ DONE
├─ A13: Responsive Variants ─[espera A6]─────┤
├─ A14: AI Layout Assist ────────────────────┤
└─ A15: Branching ───────────────────────────┘
```

---

## Archivos Resultantes

### Nuevos archivos JS (~15)
```
assets/vbp/js/
├── vbp-smart-guides.js
├── vbp-spacing-indicators.js
├── vbp-bulk-edit.js
├── vbp-constraints.js
├── vbp-animation-builder.js
├── vbp-asset-manager.js
├── vbp-global-styles.js
├── vbp-prototype-mode.js
├── vbp-responsive-variants.js
├── vbp-ai-layout.js
├── vbp-branching.js
└── vbp-realtime-collab.js
```

### Nuevos archivos CSS (~8)
```
assets/vbp/css/
├── smart-guides.css
├── spacing-indicators.css
├── animation-builder.css
├── asset-manager.css
├── global-styles.css
├── prototype-mode.css
├── responsive-variants.css
└── branching.css
```

### Nuevos archivos PHP (~5)
```
includes/visual-builder-pro/
├── class-vbp-global-styles.php
├── class-vbp-asset-manager.php
├── class-vbp-animation-builder.php
├── class-vbp-branching.php
└── class-vbp-realtime-server.php
```

### Nuevas vistas (~4)
```
includes/visual-builder-pro/views/
├── panel-animation-builder.php
├── panel-asset-manager.php
├── panel-global-styles.php
└── panel-prototype.php
```

---

## Comandos de Ejecución

### Opción A: Todo en paralelo (máximo rendimiento)
```
Lanzar 15 agentes simultáneamente
- Pros: Más rápido
- Contras: Posibles conflictos en archivos compartidos
```

### Opción B: Por fases (recomendado)
```
1. Lanzar 5 agentes Fase 1
2. Esperar completar
3. Lanzar 5 agentes Fase 2
4. Esperar completar
5. Lanzar 5 agentes Fase 3 (respetando dependencias)
```

### Opción C: Híbrido
```
1. Lanzar Fase 1 (5 agentes)
2. Lanzar Fase 2 sin dependencias (A8, A9, A10) mientras Fase 1 termina
3. Lanzar resto cuando dependencias estén listas
```

---

## Validación Post-Implementación

```bash
# Verificar sintaxis PHP
find includes/visual-builder-pro -name "*.php" -exec php -l {} \;

# Verificar sintaxis JS
find assets/vbp/js -name "*.js" ! -name "*.min.js" -exec node -c {} \;

# Verificar que assets se cargan
grep -r "wp_enqueue" includes/visual-builder-pro/class-vbp-editor.php

# Test funcional básico
curl -s "http://sitio.local/wp-json/flavor-vbp/v1/claude/status"
```

---

## Riesgos y Mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| Conflictos en class-vbp-editor.php | Cada agente agrega al final, no modifica existente |
| Conflictos en vbp-store.js | Usar extensión (no modificar), o asignar a un solo agente |
| WebSockets requiere servidor | Implementar con fallback a Heartbeat |
| Animation Builder complejo | Empezar con subset de propiedades |

---

## Aprobación

¿Proceder con la implementación?

- [ ] Opción A: Todo en paralelo (15 agentes)
- [ ] Opción B: Por fases (5+5+5 agentes)
- [ ] Opción C: Híbrido
- [ ] Modificar plan primero
