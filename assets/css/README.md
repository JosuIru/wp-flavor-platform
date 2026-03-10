# Sistema CSS de Flavor Platform

## Estructura

```
assets/css/
├── core/              # Variables, reset, tipografía, animaciones
│   ├── design-tokens.css
│   ├── design-tokens-compat.css
│   ├── flavor-base.css
│   ├── breakpoints.css
│   └── animations.css
├── components/        # Componentes reutilizables
│   ├── components.css
│   ├── form-validation.css
│   ├── tooltips.css
│   ├── breadcrumbs.css
│   ├── ajax-loading.css
│   ├── skip-links.css
│   ├── contextual-help.css
│   ├── keyboard-shortcuts.css
│   └── undo-redo.css
├── layouts/           # Estructuras de página
│   ├── layouts.css
│   ├── dashboard*.css
│   ├── portal.css
│   ├── unified-*.css
│   └── user-dashboard.css
├── modules/           # Estilos de módulos específicos
│   ├── chat-widget.css
│   ├── mi-red-social.css
│   ├── network-frontend.css
│   ├── notifications.css
│   ├── flavor-modules.css
│   └── widget-shortcodes.css
├── admin/             # Estilos de administración
│   ├── admin-assistant.css
│   ├── admin-docs.css
│   ├── admin-modals.css
│   ├── admin-shortcuts.css
│   ├── page-builder.css
│   └── ai-template-assistant.css
├── dist/              # CSS compilado (generado)
│   ├── flavor-core.bundle.css
│   └── flavor-core.min.css
└── flavor-core.css    # Punto de entrada principal
```

## Comandos

```bash
# Compilar CSS (bundle + minify)
npm run css:build

# Solo crear bundle (desarrollo)
npm run css:bundle

# Solo minificar (producción)
npm run css:minify

# Watch mode (desarrollo)
npm run css:watch
```

## Uso en WordPress

### Opción 1: Bundle completo (recomendado para producción)
```php
wp_enqueue_style(
    'flavor-core',
    FLAVOR_CHAT_IA_URL . 'assets/css/dist/flavor-core.min.css',
    [],
    FLAVOR_CHAT_IA_VERSION
);
```

### Opción 2: Archivos individuales (desarrollo)
```php
wp_enqueue_style('flavor-design-tokens', FLAVOR_CHAT_IA_URL . 'assets/css/core/design-tokens.css');
wp_enqueue_style('flavor-base', FLAVOR_CHAT_IA_URL . 'assets/css/core/flavor-base.css');
// etc.
```

## Configuración

- `postcss.config.js` - Configuración de PostCSS
- `package.json` - Scripts de build

## Dependencias

- postcss - Procesador CSS
- postcss-import - Inline de @import
- autoprefixer - Prefijos de vendor
- cssnano - Minificación

## Organización completada

✅ Fase 1: Bundle principal creado
✅ Fase 2: Archivos organizados en subdirectorios
✅ Fase 3: Imports actualizados en flavor-core.css

### Categorías de archivos:

| Directorio | Contenido |
|------------|-----------|
| `core/` | Design tokens, reset, tipografía, animaciones |
| `components/` | Elementos UI reutilizables |
| `layouts/` | Estructuras de dashboard y portal |
| `modules/` | Estilos específicos de funcionalidades |
| `admin/` | Estilos del panel de administración |
| `dist/` | Bundles compilados |
