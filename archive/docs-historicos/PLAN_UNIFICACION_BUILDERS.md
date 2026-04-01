# Plan de Unificación de Page Builders

**Fecha**: 2026-02-11
**Objetivo**: Unificar Landing Editor y Web Builder Pro en un sistema moderno

## 📊 Análisis de Sistemas Actuales

### Landing Editor (`includes/editor/class-landing-editor.php`)
- **Tamaño**: 2,676 líneas
- **Enfoque**: Secciones predefinidas (Hero, Features, CTA, Testimonios, etc.)
- **Estructura**: JSON en `_flavor_landing_structure`
- **Ventajas**:
  - ✅ Interfaz simple y amigable
  - ✅ Secciones con variantes predefinidas
  - ✅ Sistema de historial (undo/redo)
  - ✅ Autosave
  - ✅ Templates JSON
  - ✅ 12 tipos de secciones disponibles

### Web Builder Pro (`addons/flavor-web-builder-pro/`)
- **Tamaño**: 9,321 líneas
- **Enfoque**: Sistema de componentes flexibles
- **Estructura**: Meta fields custom
- **CPT**: Registra `flavor_landing`
- **Ventajas**:
  - ✅ Sistema de componentes modulares
  - ✅ Preview en tiempo real
  - ✅ Importación de templates web
  - ✅ Más flexible y potente
  - ✅ Trabajar con posts y pages

### Conflictos Actuales
- ❌ Ambos trabajan con el mismo CPT `flavor_landing`
- ❌ Interfaces separadas
- ❌ Formatos de guardado diferentes
- ❌ Duplicación de funcionalidad
- ❌ Confusión para el usuario

## 🎯 Sistema Unificado: Flavor Visual Builder

### Arquitectura Propuesta

```
Flavor_Visual_Builder (Clase Base Unificada)
│
├── Modo Secciones (Simple - Para principiantes)
│   ├── Secciones predefinidas con variantes
│   ├── Drag & drop visual
│   ├── Edición inline de contenido
│   └── Templates ready-to-use
│
├── Modo Componentes (Avanzado - Para expertos)
│   ├── Componentes individuales
│   ├── Layout personalizado
│   ├── Máxima flexibilidad
│   └── Código personalizado
│
└── Features Compartidas
    ├── Historial (undo/redo)
    ├── Autosave
    ├── Preview en tiempo real
    ├── Templates & Presets
    ├── Export/Import JSON
    ├── Responsive design tools
    └── Sistema de themes
```

## 🔧 Implementación

### Fase 1: Crear Base Unificada ✅ (Esta sesión)

**Archivo**: `includes/visual-builder/class-visual-builder.php`

```php
class Flavor_Visual_Builder {
    // Unifica ambos sistemas
    // Mantiene compatibilidad hacia atrás
    // Modo secciones + modo componentes
}
```

**Características**:
- ✅ Singleton pattern
- ✅ Detección automática de modo
- ✅ Migración automática de datos antiguos
- ✅ API unificada

### Fase 2: Componentes Core

**Directorio**: `includes/visual-builder/components/`

1. **Secciones Predefinidas** (del Landing Editor):
   - Hero (5 variantes)
   - Features (5 variantes)
   - Testimonios (3 variantes)
   - Pricing (4 variantes)
   - CTA (4 variantes)
   - FAQ (2 variantes)
   - Stats (3 variantes)
   - Newsletter (2 variantes)

2. **Componentes Individuales** (del Web Builder Pro):
   - Heading
   - Paragraph
   - Button
   - Image
   - Video
   - Form
   - Spacer
   - Divider
   - Icons
   - Grid/Columns

### Fase 3: Interfaz Unificada

**Archivo**: `includes/visual-builder/views/builder-interface.php`

**Diseño**:
```
┌─────────────────────────────────────────────────────┐
│ Flavor Visual Builder                    [👁 Preview] │
├───────────┬─────────────────────────────────────────┤
│           │                                         │
│ SECCIONES │                                         │
│  Hero     │                                         │
│  Features │         CANVAS PRINCIPAL                │
│  CTA      │        (Drag & Drop Area)               │
│  ...      │                                         │
│           │                                         │
│ ────────  │                                         │
│           │                                         │
│ COMPONENTES│                                         │
│  Heading  │                                         │
│  Button   │                                         │
│  Image    │                                         │
│  ...      │                                         │
│           │                                         │
│ [🎨 Modo] │                                         │
└───────────┴─────────────────────────────────────────┘
```

**Modos**:
- 🎨 **Modo Simple**: Secciones predefinidas
- ⚙️ **Modo Avanzado**: Componentes individuales
- 🔀 **Cambio dinámico** entre modos

### Fase 4: Sistema de Datos

**Meta Keys Unificadas**:
```php
_flavor_visual_builder_mode     = 'sections' | 'components'
_flavor_visual_builder_data     = JSON unificado
_flavor_visual_builder_version  = '1.0'
_flavor_visual_builder_legacy   = datos antiguos (backup)
```

**Formato JSON Unificado**:
```json
{
  "mode": "sections",
  "version": "1.0",
  "settings": {
    "responsive": true,
    "animation": true
  },
  "content": [
    {
      "type": "section",
      "component": "hero",
      "variant": "centrado",
      "data": {...}
    },
    {
      "type": "component",
      "component": "button",
      "data": {...}
    }
  ]
}
```

### Fase 5: Migración Automática

**Script**: `includes/visual-builder/class-migration.php`

```php
class Flavor_Visual_Builder_Migration {
    // Detecta formato antiguo
    // Convierte automáticamente
    // Mantiene backup
    // Sin pérdida de datos
}
```

**Proceso**:
1. Detectar páginas con formato antiguo
2. Convertir a formato unificado
3. Guardar backup en `_flavor_visual_builder_legacy`
4. Actualizar meta keys
5. Log de migración

## 📋 Checklist de Implementación

### Fase 1: Base ✅
- [ ] Crear directorio `includes/visual-builder/`
- [ ] Implementar `class-visual-builder.php`
- [ ] Singleton pattern
- [ ] Sistema de hooks
- [ ] Registrar CPT unificado
- [ ] Detección de modo

### Fase 2: Componentes
- [ ] Crear `components/` directory
- [ ] Implementar secciones del Landing Editor
- [ ] Implementar componentes del Web Builder Pro
- [ ] Sistema de registro de componentes
- [ ] Variantes y presets

### Fase 3: Interfaz
- [ ] Diseñar UI unificada
- [ ] Drag & drop functionality
- [ ] Editor inline
- [ ] Panel de propiedades
- [ ] Preview en tiempo real
- [ ] Selector de modo

### Fase 4: Features
- [ ] Historial (undo/redo)
- [ ] Autosave
- [ ] Templates
- [ ] Export/Import
- [ ] Responsive tools
- [ ] Theme integration

### Fase 5: Migración
- [ ] Script de migración
- [ ] Detección automática
- [ ] Backup de datos
- [ ] Testing exhaustivo
- [ ] Documentación

### Fase 6: Deprecation
- [ ] Marcar sistemas antiguos como deprecated
- [ ] Mantener por 6 meses
- [ ] Avisos de migración
- [ ] Eliminar código antiguo

## 🎨 Mejoras del Sistema Unificado

### Nuevas Features
1. **AI Assistant** 🤖
   - Sugerencias de diseño
   - Auto-complete de contenido
   - Optimización SEO

2. **Design System** 🎨
   - Paletas de colores
   - Tipografías predefinidas
   - Espaciados consistentes
   - Design tokens

3. **Colaboración** 👥
   - Comentarios en secciones
   - Historial de cambios
   - Roles y permisos

4. **Performance** ⚡
   - Lazy loading de componentes
   - Code splitting
   - Optimización de imágenes
   - Critical CSS

5. **Analytics** 📊
   - Tracking de conversiones
   - Heatmaps
   - A/B testing
   - Performance metrics

## 📚 Documentación

### Para Usuarios
- [ ] Guía de inicio rápido
- [ ] Video tutoriales
- [ ] Ejemplos y templates
- [ ] FAQ

### Para Desarrolladores
- [ ] API documentation
- [ ] Crear componentes custom
- [ ] Extender funcionalidad
- [ ] Hooks y filters

## 🔄 Compatibilidad

### Hacia Atrás
- ✅ Landing Editor → Visual Builder (automático)
- ✅ Web Builder Pro → Visual Builder (automático)
- ✅ Mantener shortcodes antiguos
- ✅ Backup automático

### Hacia Adelante
- ✅ Formato JSON extensible
- ✅ API de componentes
- ✅ Sistema de plugins
- ✅ Integración con builders externos

## 🚀 Roadmap

### v1.0 - Unificación Base (2 semanas)
- ✅ Sistema unificado funcional
- ✅ Migración automática
- ✅ Secciones + Componentes básicos
- ✅ UI moderna

### v1.1 - Features Avanzadas (2 semanas)
- ⏳ AI Assistant
- ⏳ Design System
- ⏳ Templates premium
- ⏳ Responsive tools avanzados

### v1.2 - Colaboración (2 semanas)
- ⏳ Sistema de comentarios
- ⏳ Roles y permisos
- ⏳ Historial detallado
- ⏳ Team workspace

### v2.0 - Ecosistema (1 mes)
- ⏳ Marketplace de componentes
- ⏳ Integraciones (Figma, Sketch)
- ⏳ API pública
- ⏳ SDK para desarrolladores

## 💡 Beneficios Esperados

### Para Usuarios
- ✅ Una sola interfaz para todo
- ✅ Más fácil de aprender
- ✅ Más potente y flexible
- ✅ Mejor experiencia
- ✅ Sin pérdida de datos

### Para Desarrollo
- ✅ Código más limpio
- ✅ Mantenimiento más fácil
- ✅ Extensible
- ✅ Mejor organización
- ✅ Testing más simple

### Para Performance
- ✅ Menos código duplicado
- ✅ Optimización conjunta
- ✅ Cacheo mejorado
- ✅ Carga más rápida

---

**Próximo Paso**: Implementar Fase 1 - Base Unificada

*Plan creado el 2026-02-11*
