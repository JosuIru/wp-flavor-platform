# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Pendiente
- Sistema de plugins de terceros para VBP
- Exportacion a Figma nativa
- Modo presentacion para clientes

---

## [3.5.0] - 2026-04-01

### Anadido
- **Sistema de Discovery** - Scripts obligatorios antes de componer paginas/APKs
  - `tools/full-inventory.sh` - Inventario completo 3 fases
  - `tools/vbp-inventory.sh` - Inventario Visual Builder Pro
  - `tools/apk-inventory.sh` - Inventario APK 3 niveles
- **API de Compatibilidad de Modulos** (`/flavor-platform/v1/modules/compatibility`)
- **Pre-commit hook** para validar modulos antes de commit
- **Addon Multilingual v1.4.0** completo
  - 94 tests unitarios
  - Object cache con wp_cache
  - Documentacion OpenAPI
  - Assets minificados (45% reduccion)
  - Editor side-by-side con atajos de teclado
  - Sistema de comentarios de traduccion

### Mejorado
- **CLAUDE.md** con reglas de discovery obligatorio
- **CLAUDE-APK.md** nueva documentacion para apps moviles
- **README.md** simplificado a quickstart

### Limpieza
- 34 archivos .md historicos movidos a `archive/docs-historicos/`
- Estructura de documentacion consolidada

---

## [3.4.0] - 2026-03-23

### Anadido
- **Sistema de Versionado de Modulos** con `module.json`
- **Visual Builder Pro mejorado** - Split-screen preview
- **Modularizacion de keyboard shortcuts** en VBP

### Arquitectura
- Bootstrap modular refactorizado
- Sistema de migrations con WP-CLI

---

## [3.3.0] - 2026-03-10

### Anadido
- **Federacion completa** - Sincronizacion entre nodos WordPress
- **Webhooks** con firma HMAC-SHA256
- **8 shortcodes de red** para contenido federado
- **Apps moviles extendidas** - 55 templates Flutter

### Seguridad
- Correccion de vulnerabilidades en endpoints A/B testing
- Mejoras en endpoints publicos de blog

---

## [3.2.0] - 2026-02-23

### Anadido
- **Addon Multilingual** - Sistema de traduccion multiidioma
- **Templates dinamicos** para CPTs en VBP
- **REST API para Claude Code** (`/flavor-vbp/v1/claude/`)

### Rendimiento
- Lazy loading de widgets en dashboard
- Consolidacion de queries N+1

---

## [3.1.1] - 2026-02-12

### Seguridad
- **API Keys encriptadas** con AES-256-GCM en base de datos
- **HTTPS forzado** en aplicaciones Android (usesCleartextTraffic=false)
- **Network Security Config** para Android con cert pinning preparado
- **Autenticacion biometrica** (Face ID / huella dactilar) en apps moviles
- **Validacion de uploads mejorada** con verificacion MIME y limite 5MB
- **Headers de seguridad** en exports (X-Content-Type-Options, SHA256)

### Rendimiento
- **Lazy loading de modulos** - Reduccion 40% tiempo de carga
- **Cache de metadatos** de modulos (transient 24h)
- **Assets minificados** - 15+ archivos CSS/JS optimizados
- **Limpieza de archivos** - Eliminados node_modules, builds, scripts debug

### Arquitectura
- **Migracion V3 completa** - 43/43 modulos con `get_pages_definition()`
- **Trait de notificaciones** implementado en 41/43 modulos
- **Refactorizacion email-marketing** - 5 clases especializadas extraidas
- **Logger centralizado** en Flutter - Reemplazo de 153 debugPrint

### UX/UI
- **Sistema de validacion de formularios** con feedback en tiempo real
- **Loading states AJAX** con spinners, overlays y toasts
- **Tooltips accesibles** sin dependencias externas
- **Modo oscuro** con deteccion de preferencia del sistema
- **Breakpoints responsive** formalizados (sm/md/lg/xl/2xl)
- **Modales de confirmacion** reemplazando confirm() nativo
- **Haptic feedback** centralizado en apps Flutter
- **Semantics widgets** para accesibilidad en Flutter
- **PopScope navigation** para control de boton atras
- **Accesibilidad en formularios** - aria-labels, fieldsets, legends

### Documentacion
- **OpenAPI 3.0 completa** - 45+ endpoints documentados
- **Internacionalizacion** - Plantilla POT + traduccion EN_US
- **Reportes de auditoria** actualizados

### Testing
- **Suite PHPUnit** - 4 tests unitarios basicos
- **Suite Flutter** - 5 tests de seguridad y utilidades
- **Bootstrap de tests** con mocks de WordPress

### Correcciones
- Eliminados 23 scripts de debug del directorio raiz
- Eliminados 21 archivos MD temporales
- Corregidos 72+ usos de debugPrint por Logger

---

## [3.1.0] - 2026-02-01

### Anadido
- 43 modulos funcionales completos
- Sistema de chat con IA (Claude, OpenAI, DeepSeek, Mistral)
- Aplicaciones moviles Flutter (Android/iOS)
- Sistema de reservas y tickets
- Multi-idioma (ES, EN, EU)

### Arquitectura
- Plugin modular con autoloader PSR-4
- Sistema de hooks extensible
- APIs REST y AJAX completas
- Sistema de roles granular

---

## [3.0.0] - 2026-01-15

### Anadido
- Reescritura completa del sistema
- Nueva arquitectura modular V3
- Soporte multisite WordPress
- Sistema de addons

---

## [2.4.0] - 2025-12-20 - Visual Builder Pro

### Anadido
- **Modo Prototipado (Prototype Mode)**
  - Definir interacciones: click, hover, scroll
  - Acciones: navegar a frame, mostrar overlay, animar
  - Conectar frames con flechas
  - Preview interactivo en tiempo real
  - Exportar como HTML interactivo
  - Smart Animate entre estados

- **AI Layout Assistant mejorado**
  - Generar layouts desde descripcion natural
  - Auto-spacing inteligente
  - Sugerencias de colores complementarios
  - Generacion de variantes de diseno
  - Integracion con API de IA existente

### Mejorado
- Rendimiento del canvas en documentos grandes
- Mejor soporte para elementos SVG

---

## [2.3.0] - 2025-11-15 - Visual Builder Pro

### Anadido
- **Colaboracion en Tiempo Real (Real-time Collaboration)**
  - Servidor WebSocket para sincronizacion instantanea
  - Cursores de otros usuarios visibles en canvas
  - Locks optimistas por elemento
  - Sincronizacion de cambios con CRDT
  - Indicador visual de quien edita cada elemento
  - Fallback a WordPress Heartbeat si WebSocket falla

- **Variantes Responsive (Responsive Variants)**
  - Breakpoints: desktop (1280px+), laptop (1024px), tablet (768px), mobile (375px)
  - Guardar variante de layout por breakpoint
  - Override de propiedades por breakpoint
  - Visualizacion de diferencias entre breakpoints
  - Copiar layout de un breakpoint a otro

- **Constructor de Animaciones (Animation Builder)**
  - Panel de timeline visual con keyframes
  - Propiedades animables: transform, opacity, colors, dimensions
  - Curvas de easing predefinidas + editor bezier custom
  - Preview en canvas antes de guardar
  - Exportar como CSS @keyframes
  - Triggers: scroll-into-view, hover, click, page-load

- **Gestor de Assets (Asset Manager)**
  - Panel lateral con libreria centralizada de medios
  - Tabs: Imagenes, SVGs, Videos, Iconos, Documentos
  - Drag & drop para subir multiples archivos
  - Busqueda y filtros por tipo/fecha/tamano
  - Integracion con Unsplash (busqueda directa)
  - Sistema de favoritos y carpetas
  - Optimizacion automatica de imagenes

### Mejorado
- Panel de capas con mejor rendimiento
- Undo/redo mas rapido con snapshots incrementales

---

## [2.2.0] - 2025-10-01 - Visual Builder Pro

### Anadido
- **Sistema de Simbolos (Symbols)**
  - Crear simbolos desde seleccion (Ctrl+Shift+Y)
  - Insertar instancias vinculadas (Ctrl+Alt+O)
  - Overrides por instancia (texto, imagenes, enlaces, colores)
  - Simbolos anidados (simbolo dentro de simbolo)
  - Sistema de variantes (ej: boton primario/secundario/ghost)
  - Swap entre simbolos similares
  - Import/Export de simbolos como JSON
  - Panel de simbolos (F8) con busqueda y categorias
  - Desvincular instancia (Ctrl+Alt+U)
  - Ir al master (Ctrl+Shift+G)

- **Smart Guides**
  - Guias de alineacion automaticas al arrastrar
  - Lineas de centro (rojas) y bordes (azules)
  - Snap automatico a grid de 8px
  - Snap a bordes/centros de otros elementos
  - Configuracion de sensibilidad del snap

- **Indicadores de Espaciado (Spacing Indicators)**
  - Mostrar distancia en px entre elementos
  - Flechas bidireccionales con valor numerico
  - Activar con Alt+hover sobre cualquier elemento
  - Aparecen automaticamente al arrastrar
  - Color naranja para facil identificacion

- **Copiar/Pegar Estilos (Copy/Paste Styles)**
  - Ctrl+Alt+C para copiar estilos del elemento seleccionado
  - Ctrl+Alt+V para pegar estilos a seleccion actual
  - Incluye: colores, tipografia, espaciado, bordes, sombras
  - Notificacion toast de confirmacion
  - Funciona con seleccion multiple

- **Zoom a Seleccion (Zoom to Selection)**
  - Atajo: Ctrl+2 o doble-click en minimap
  - Calcula bounding box automaticamente
  - Aplica zoom y pan para centrar
  - Animacion suave de transicion (300ms)
  - Funciona con elementos individuales o seleccion multiple

- **Edicion Masiva (Bulk Property Edit)**
  - Seleccion multiple + inspector muestra campos comunes
  - Campos con valores mixtos muestran placeholder "Mixed"
  - Editar aplica cambios a todos los seleccionados
  - Checkbox para "aplicar solo a seleccionados"
  - Soporte para propiedades numericas, colores y texto

### Mejorado
- Rendimiento general del canvas (60fps constante)
- Mejor feedback visual durante drag & drop

---

## [2.1.0] - 2025-08-15 - Visual Builder Pro

### Anadido
- **Sistema de Branching/Versiones**
  - Crear ramas de diseno independientes
  - Merge entre branches con resolucion de conflictos
  - Diff visual entre ramas (lado a lado)
  - Nombrar y describir branches
  - Restaurar desde cualquier punto en el historial
  - Colaboracion aislada por branch

- **Constraints/Pinning (tipo Figma)**
  - Panel en inspector: Top, Right, Bottom, Left, Center H/V
  - Checkboxes visuales para anclar a cada borde
  - Al redimensionar padre, hijos anclados mantienen distancia
  - Presets rapidos: Fill, Center, Top-Left, Top-Right, etc.
  - Visualizacion en canvas de constraints activos
  - Indicadores de linea punteada mostrando anclajes

- **Estilos Globales (Global Styles)**
  - Definir clases CSS reutilizables (.heading-1, .button-primary, etc.)
  - Aplicar a elementos desde dropdown en inspector
  - Editar estilo global actualiza todas las instancias
  - Panel de gestion de estilos (crear, editar, eliminar)
  - Exportar como archivo CSS
  - Importar estilos desde CSS existente
  - Variables CSS para colores y tipografia

### Mejorado
- UI del inspector mas compacta
- Mejor organizacion de paneles laterales

---

## [2.0.0] - 2025-06-01 - Visual Builder Pro

> **RELEASE MAYOR** - Ver [Release Notes completas](docs/releases/v2.0.0.md)

### Anadido
- **Editor Visual Fullscreen** tipo Figma/Photoshop
  - Layout con toolbar superior, paneles laterales, canvas central
  - Miniaturas de pagina en panel izquierdo
  - Inspector de propiedades en panel derecho
  - Barra de estado con informacion contextual

- **Canvas con Drag & Drop**
  - Powered by SortableJS
  - Arrastrar elementos desde panel de bloques
  - Reordenar elementos arrastrando
  - Seleccion multiple (Ctrl+click, Shift+click)
  - Zoom (Ctrl+rueda, pinch en trackpad)
  - Pan (Space+arrastrar)

- **Panel de Bloques**
  - 50+ bloques predefinidos por categoria
  - Busqueda instantanea
  - Favoritos del usuario
  - Drag & drop al canvas

- **Panel de Capas**
  - Vista jerarquica de elementos
  - Drag & drop para reordenar
  - Ocultar/mostrar capas
  - Bloquear capas
  - Renombrar elementos

- **Inspector de Propiedades**
  - Tabs: Layout, Estilos, Avanzado
  - Controles visuales para espaciado, colores, tipografia
  - Preview en tiempo real

- **Historial de Versiones (Version History)**
  - Guardar snapshots manualmente
  - Auto-save cada 5 minutos
  - Restaurar versiones anteriores
  - Diff visual entre versiones
  - Limite configurable de versiones guardadas

- **Atajos de Teclado Completos**
  - Ver referencia en `docs/vbp/keyboard-shortcuts.md`
  - Paleta de comandos (Ctrl+K)
  - Todos los atajos personalizables

- **Sistema de Plugins VBP**
  - API para extensiones de terceros
  - Hooks disponibles: `vbp:init`, `vbp:save`, `vbp:load`, etc.
  - Panel de gestion de plugins
  - Documentacion para desarrolladores

- **Temas del Editor**
  - Light (predeterminado)
  - Dark
  - Midnight (OLED)
  - Forest (verde oscuro)
  - High Contrast (accesibilidad)
  - Temas personalizados via CSS variables

- **Accesibilidad (a11y)**
  - ARIA completo en todos los controles
  - Navegacion por teclado de todos los paneles
  - Screen reader support (testeado con NVDA, VoiceOver)
  - Respeta prefers-reduced-motion
  - Contraste minimo WCAG AA

- **Modo Offline**
  - Service Worker para cache de assets
  - IndexedDB para almacenar cambios
  - Indicador de estado de conexion
  - Sincronizacion automatica al reconectar
  - Cola de cambios pendientes

- **Monitor de Rendimiento**
  - Metricas en tiempo real (FPS, memoria)
  - Alertas automaticas si rendimiento baja
  - Sugerencias de optimizacion
  - Historial de metricas

- **Design Tokens**
  - Definir tokens de diseno (colores, espaciado, tipografia)
  - Sincronizacion con Figma (import/export)
  - Export a multiples formatos: CSS, SCSS, JSON, Tailwind
  - Panel de gestion de tokens

- **API de Claude mejorada**
  - Endpoints para todas las features VBP
  - Operaciones batch (crear/actualizar multiples elementos)
  - Documentacion contextual en cada endpoint
  - Ejemplos de uso en documentacion

### Cambiado
- Migrado de jQuery UI a SortableJS
- Nuevo sistema de estados con Alpine.js
- Arquitectura de componentes modular

### Seguridad
- Rate limiting en API (100 requests/minuto)
- Headers de seguridad HTTP (X-Content-Type-Options, X-Frame-Options)
- Comparacion timing-safe de API keys con hash_equals()
- Logging de intentos de acceso fallidos

### Obsoleto
- Editor clasico de VBP 1.x (mantener solo para migracion)

### Eliminado
- Dependencia de jQuery UI
- Panel de widgets legacy

---

## [1.x.x] - Versiones Legacy

Las versiones 1.x fueron la primera iteracion del Visual Builder, con funcionalidad limitada. Se recomienda migrar a 2.0+.

### [1.2.0] - 2025-03-01
- Ultimo release de la rama 1.x
- Modo de compatibilidad para migracion a 2.0

### [1.1.0] - 2025-01-15
- Mejoras de estabilidad
- Correccion de bugs criticos

### [1.0.0] - 2024-11-01
- Release inicial del Visual Builder
- Funcionalidad basica de arrastrar y soltar

---

## Enlaces

[Unreleased]: https://github.com/flavor/flavor-platform/compare/v3.5.0...HEAD
[3.5.0]: https://github.com/flavor/flavor-platform/compare/v3.4.0...v3.5.0
[3.4.0]: https://github.com/flavor/flavor-platform/compare/v3.3.0...v3.4.0
[3.3.0]: https://github.com/flavor/flavor-platform/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/flavor/flavor-platform/compare/v3.1.1...v3.2.0
[3.1.1]: https://github.com/flavor/flavor-platform/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/flavor/flavor-platform/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/flavor/flavor-platform/compare/v2.4.0...v3.0.0
[2.4.0]: https://github.com/flavor/flavor-platform/compare/v2.3.0...v2.4.0
[2.3.0]: https://github.com/flavor/flavor-platform/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/flavor/flavor-platform/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/flavor/flavor-platform/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/flavor/flavor-platform/releases/tag/v2.0.0
