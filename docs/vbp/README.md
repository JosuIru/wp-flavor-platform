# Visual Builder Pro - Documentacion

Editor visual fullscreen tipo Figma/Photoshop para WordPress, integrado en Flavor Platform.

## Inicio Rapido

- [Guia de Inicio](getting-started.md) - Configuracion inicial y primeros pasos
- [Atajos de Teclado](keyboard-shortcuts.md) - Referencia completa de atajos
- [Indice de Busqueda](SEARCH-INDEX.md) - Buscar por palabra clave

---

## Tabla de Features

### Core

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Editor | Completo | Vista general del editor | [Ver](features/editor-overview.md) |
| Canvas | Completo | Area de trabajo central | [Ver](features/canvas.md) |
| Inspector | Completo | Panel de propiedades | [Ver](features/inspector.md) |
| Capas | Completo | Panel de jerarquia | [Ver](features/layers.md) |
| Undo/Redo | Completo | Sistema de historial | [Ver](features/undo-redo.md) |

### Fase 1: Quick Wins

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Smart Guides | Completo | Guias de alineacion automaticas | [Ver](features/smart-guides.md) |
| Spacing Indicators | Completo | Indicadores de distancia | [Ver](features/spacing-indicators.md) |
| Copy/Paste Styles | Completo | Copiar y pegar estilos | [Ver](features/copy-paste-styles.md) |
| Zoom to Selection | Completo | Zoom automatico a seleccion | [Ver](features/zoom-selection.md) |
| Bulk Edit | Completo | Edicion masiva | [Ver](features/bulk-edit.md) |

### Fase 2: Medium Effort

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Constraints | Completo | Sistema de anclaje/pinning | [Ver](features/constraints.md) |
| Animation Builder | Completo | Constructor de animaciones | [Ver](features/animation-builder.md) |
| Asset Manager | Completo | Gestor de medios | [Ver](features/asset-manager.md) |
| Global Styles | Completo | Estilos globales | [Ver](features/global-styles.md) |
| Simbolos | Completo | Componentes reutilizables | [Ver](features/symbols.md) |

### Fase 3: High Effort

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Realtime Collab | Completo | Colaboracion en tiempo real | [Ver](features/realtime-collab.md) |
| Prototype Mode | Completo | Prototipado interactivo | [Ver](features/prototype-mode.md) |
| Responsive Variants | Completo | Variantes por breakpoint | [Ver](features/responsive-variants.md) |
| AI Layout | Completo | Asistente de diseno con IA | [Ver](features/ai-layout.md) |
| Branching | Completo | Sistema de ramas de diseno | [Ver](features/branching.md) |

### Extras

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Plugin System | Completo | Arquitectura extensible | [Ver](features/plugin-system.md) |
| Editor Themes | Completo | Temas claro/oscuro | [Ver](features/editor-themes.md) |
| Accesibilidad | Completo | Herramientas a11y | [Ver](features/accessibility.md) |
| Modo Offline | Completo | Trabajo sin conexion | [Ver](features/offline-mode.md) |
| Performance Monitor | Completo | Monitoreo de rendimiento | [Ver](features/performance-monitor.md) |
| Design Tokens | Completo | Tokens de diseno | [Ver](features/design-tokens.md) |

### Mejoras Recientes

| Feature | Estado | Descripcion | Documentacion |
|---------|--------|-------------|---------------|
| Scroll Animations | Completo | Animaciones por scroll | [Ver](features/scroll-animations.md) |
| Advanced Animations | Completo | Stagger, motion path, spring | [Ver](features/advanced-animations.md) |
| 3D/WebGL | Completo | Escenas y modelos 3D | [Ver](features/3d-webgl.md) |
| Variables y Logica | Completo | Variables dinamicas | [Ver](features/variables-logic.md) |

---

## Referencia API

- [Endpoints REST](api/rest-endpoints.md) - API REST completa
- [Alpine Stores](api/alpine-stores.md) - Stores de Alpine.js

---

## Arquitectura del Sistema

```
+---------------------------------------------------------------------+
|                        TOOLBAR SUPERIOR                              |
|  [Logo] [Undo][Redo] | [Desktop][Tablet][Mobile] | [Zoom] [Save]    |
+----------+---------------------------------------------+-------------+
|  PANEL   |                 C A N V A S                 | INSPECTOR   |
|  BLOQUES |           (Drag & Drop con Sortable)        |  ESTILOS    |
|          |                                             |             |
|  CAPAS   |  [Elementos editables inline]               |   LAYOUT    |
+----------+---------------------------------------------+-------------+
|                        STATUS BAR                                    |
+---------------------------------------------------------------------+
```

---

## Estructura de Archivos

### PHP (Backend)

| Archivo | Descripcion |
|---------|-------------|
| `class-vbp-loader.php` | Inicializa y carga componentes |
| `class-vbp-editor.php` | Controlador principal |
| `class-vbp-rest-api.php` | Endpoints REST |
| `class-vbp-block-library.php` | Bloques disponibles |
| `class-vbp-canvas.php` | Renderizado del canvas |
| `class-vbp-symbols.php` | Sistema de simbolos |
| `class-vbp-branching.php` | Sistema de ramas |
| `class-vbp-realtime-server.php` | Servidor colaboracion |
| `class-vbp-global-styles.php` | Estilos globales |
| `class-vbp-asset-manager.php` | Gestor de assets |
| `class-vbp-plugin-system.php` | Sistema de plugins |
| `ai/class-vbp-ai-layout.php` | Asistente de IA |

### JavaScript (Frontend)

| Archivo | Descripcion |
|---------|-------------|
| `vbp-store.js` | Estado global (Alpine.js) |
| `vbp-app.js` | Aplicacion principal |
| `vbp-canvas.js` | Canvas + drag & drop |
| `vbp-symbols.js` | Sistema de simbolos |
| `vbp-realtime-collab.js` | Cliente colaboracion |
| `vbp-prototype-mode.js` | Modo prototipado |
| `vbp-responsive-variants.js` | Variantes responsive |
| `vbp-animation-builder.js` | Animaciones |
| `vbp-advanced-animations.js` | Animaciones avanzadas |
| `vbp-scroll-animations.js` | Animaciones scroll |
| `vbp-3d-scene.js` | Escenas 3D |
| `vbp-variables.js` | Variables y logica |
| `vbp-ai-layout.js` | Cliente AI Layout |
| `vbp-constraints.js` | Sistema constraints |
| `vbp-bulk-edit.js` | Edicion masiva |
| `vbp-global-styles.js` | Estilos globales |
| `vbp-asset-manager.js` | Gestor de assets |
| `vbp-offline-sync.js` | Sincronizacion offline |
| `vbp-performance-monitor.js` | Monitor rendimiento |
| `vbp-accessibility.js` | Herramientas a11y |
| `vbp-editor-themes.js` | Temas del editor |

---

## Requisitos

- WordPress 6.0+
- PHP 7.4+
- JavaScript ES6
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

---

## Changelog

### Version 2.5.0 (Actual)
- Nuevo: Animaciones de Scroll avanzadas
- Nuevo: Animaciones con stagger, spring, motion path
- Nuevo: Soporte 3D/WebGL
- Nuevo: Sistema de variables y logica
- Nuevo: Design tokens con modos
- Nuevo: Performance monitor mejorado

### Version 2.4.0
- Nuevo: Modo Prototipado con interacciones
- Nuevo: AI Layout Assistant mejorado
- Nuevo: Sistema de plugins extensible
- Nuevo: Temas de editor personalizables

### Version 2.3.0
- Nuevo: Colaboracion en tiempo real
- Nuevo: Responsive Variants
- Nuevo: Animation Builder
- Nuevo: Asset Manager
- Nuevo: Modo Offline

### Version 2.2.0
- Nuevo: Sistema de Simbolos
- Nuevo: Smart Guides y Spacing Indicators
- Nuevo: Bulk Edit
- Nuevo: Herramientas de accesibilidad

### Version 2.1.0
- Nuevo: Sistema de Branching
- Nuevo: Constraints/Pinning
- Nuevo: Global Styles

---

## Recursos Adicionales

- [CLAUDE.md](../../CLAUDE.md) - Instrucciones para Claude Code
- [Guiones Video](../GUIONES-VIDEO-TUTORIALES.md) - Tutoriales
- [API Reference](../api/CLAUDE-API-GUIDE.md) - Guia completa APIs
