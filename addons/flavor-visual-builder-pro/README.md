# Visual Builder Pro (VBP)

Editor visual fullscreen tipo Photoshop/Figma para WordPress.

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                      TOOLBAR SUPERIOR                            │
│  [Logo] [Undo][Redo] │ [Desktop][Tablet][Mobile] │ [Zoom] [Save] │
├──────────┬─────────────────────────────────────────┬────────────┤
│  PANEL   │              C A N V A S               │  INSPECTOR  │
│  BLOQUES │         (Drag & Drop con Sortable)      │   ESTILOS   │
│          │                                         │             │
│  CAPAS   │  [Elementos editables inline]           │   LAYOUT    │
└──────────┴─────────────────────────────────────────┴────────────┘
```

## Estructura de Archivos

### PHP (Backend)

| Archivo | Descripción |
|---------|-------------|
| `class-vbp-loader.php` | Inicializa y carga todos los componentes |
| `class-vbp-editor.php` | Controlador principal, hooks, assets |
| `class-vbp-block-library.php` | Registro de bloques disponibles |
| `class-vbp-canvas.php` | Renderizado del canvas y frontend |
| `class-vbp-rest-api.php` | Endpoints REST API |

### Views (Templates PHP)

| Archivo | Descripción |
|---------|-------------|
| `editor-fullscreen.php` | Template principal del editor |
| `panel-blocks.php` | Panel de bloques arrastrables |
| `panel-layers.php` | Panel de capas (show/hide/lock) |
| `panel-inspector.php` | Inspector de estilos |
| `toolbar-floating.php` | Toolbar de texto flotante |

### JavaScript

| Archivo | Descripción |
|---------|-------------|
| `vbp-performance.js` | Utilidades de performance (debounce, memoize) |
| `vbp-store.js` | Estado global con Alpine.js |
| `vbp-app.js` | Componente principal de la aplicación |
| `vbp-canvas.js` | Canvas + SortableJS drag & drop |
| `vbp-layers.js` | Panel de capas |
| `vbp-inspector.js` | Inspector de estilos |
| `vbp-rulers.js` | Reglas y guías |
| `vbp-text-editor.js` | Editor de texto enriquecido |
| `vbp-keyboard.js` | Atajos de teclado |
| `vbp-history.js` | Undo/Redo |
| `vbp-api.js` | Cliente REST API |

### CSS

| Archivo | Descripción |
|---------|-------------|
| `editor-core.css` | Layout principal, variables CSS |
| `editor-canvas.css` | Canvas, elementos, drag states |
| `editor-panels.css` | Paneles laterales, modales |
| `editor-rulers.css` | Reglas y guías |
| `editor-toolbar.css` | Toolbars |
| `editor-responsive.css` | Media queries |

## Store (Estado Global)

El estado se gestiona con `Alpine.store('vbp')`:

```javascript
{
    // Documento
    postId: 0,
    isDirty: false,
    elements: [],
    settings: { pageWidth, backgroundColor, customCss },

    // UI
    zoom: 100,
    devicePreview: 'desktop',
    panels: { blocks, inspector, layers },

    // Selección
    selection: { elementIds: [], multiSelect: false },

    // Historial
    history: { past: [], future: [] }
}
```

## Estructura de Elemento

```javascript
{
    id: 'el_abc123',
    type: 'hero',
    variant: 'centered',
    name: 'Hero Principal',
    visible: true,
    locked: false,
    data: {
        titulo: '...',
        subtitulo: '...',
        // ...campos específicos del tipo
    },
    styles: {
        spacing: { margin: {}, padding: {} },
        colors: { background: '', text: '' },
        typography: { fontSize: '', fontWeight: '' },
        borders: { radius: '', width: '', color: '' },
        shadows: { boxShadow: '' },
        layout: { display: '', flexDirection: '' },
        advanced: { cssId: '', cssClasses: '', customCss: '' }
    },
    children: []
}
```

## API REST Endpoints

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor-vbp/v1/documents/{id}` | GET | Cargar documento |
| `/flavor-vbp/v1/documents/{id}` | POST | Guardar documento |
| `/flavor-vbp/v1/documents/{id}/revisions` | GET | Historial de revisiones |
| `/flavor-vbp/v1/render-element` | POST | Preview de elemento |
| `/flavor-vbp/v1/blocks` | GET | Librería de bloques |

## Atajos de Teclado

| Atajo | Acción |
|-------|--------|
| `Ctrl+S` | Guardar |
| `Ctrl+Z` | Deshacer |
| `Ctrl+Shift+Z` | Rehacer |
| `Ctrl+C/X/V` | Copiar/Cortar/Pegar |
| `Ctrl+D` | Duplicar |
| `Delete` | Eliminar |
| `Escape` | Deseleccionar |
| `Ctrl+A` | Seleccionar todo |
| `+/-` | Zoom in/out |
| `Ctrl+K` | Paleta de comandos |
| `?` | Mostrar ayuda |

## Performance

El editor incluye optimizaciones:

- **Índice de elementos O(1)**: Búsquedas rápidas por ID
- **Debounce**: Autosave cada 3 segundos
- **Memoización**: Cache de resultados frecuentes
- **RAF Throttle**: Animaciones suaves
- **Lazy Load**: Carga diferida de categorías
- **Virtual Scroll Helper**: Para listas largas

## Accesibilidad

- Skip links para navegación rápida
- ARIA roles completos (dialog, tablist, listbox)
- Soporte para navegación por teclado
- `prefers-reduced-motion` respetado
- Alto contraste soportado
- Screen reader compatible

## Extensibilidad

### Registrar nuevos bloques

```php
add_filter('vbp_register_blocks', function($blocks) {
    $blocks['mi_bloque'] = [
        'name' => 'Mi Bloque',
        'category' => 'custom',
        'icon' => '<svg>...</svg>',
        'fields' => [...],
        'render_callback' => 'mi_render_callback'
    ];
    return $blocks;
});
```

### Hooks disponibles

- `vbp_loaded` - VBP inicializado
- `vbp_before_save` - Antes de guardar
- `vbp_after_save` - Después de guardar
- `vbp_render_element` - Al renderizar elemento

## Requisitos

- WordPress 6.0+
- PHP 7.4+
- JavaScript ES6
- Navegadores modernos (Chrome, Firefox, Safari, Edge)
