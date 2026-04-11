# Temas del Editor

Sistema de personalizacion visual del editor VBP con temas claros, oscuros y personalizados.

## Descripcion

Los Temas del Editor permiten cambiar la apariencia visual del entorno de trabajo VBP. Incluye temas predefinidos (claro, oscuro, alto contraste) y la posibilidad de crear temas personalizados.

## Como Acceder

- Menu: View > Theme
- Configuracion: `Ctrl+,` > Apariencia
- Paleta de comandos: "theme"

## Temas Predefinidos

### Light (Claro)

Tema por defecto con fondo claro, ideal para ambientes luminosos.

| Variable | Valor |
|----------|-------|
| Background | #ffffff |
| Surface | #f3f4f6 |
| Text | #1f2937 |
| Primary | #3b82f6 |
| Border | #e5e7eb |

### Dark (Oscuro)

Tema oscuro que reduce fatiga visual en ambientes con poca luz.

| Variable | Valor |
|----------|-------|
| Background | #111827 |
| Surface | #1f2937 |
| Text | #f9fafb |
| Primary | #60a5fa |
| Border | #374151 |

### High Contrast (Alto Contraste)

Tema con contraste maximizado para accesibilidad.

| Variable | Valor |
|----------|-------|
| Background | #000000 |
| Surface | #1a1a1a |
| Text | #ffffff |
| Primary | #00ff00 |
| Border | #ffffff |

### Auto (Sistema)

Sigue la preferencia del sistema operativo.

```javascript
// Detecta automaticamente
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    // Aplica tema oscuro
}
```

## Configuracion

### Cambiar Tema

```javascript
const themes = window.VBPEditorThemes;

// Establecer tema
themes.setTheme('dark');    // 'light', 'dark', 'high-contrast', 'auto'

// Obtener tema actual
const current = themes.getTheme();

// Toggle dark/light
themes.toggleDarkMode();
```

### Variables CSS

El tema se aplica mediante variables CSS:

```css
:root[data-vbp-theme="dark"] {
    --vbp-bg-primary: #111827;
    --vbp-bg-secondary: #1f2937;
    --vbp-bg-tertiary: #374151;
    --vbp-text-primary: #f9fafb;
    --vbp-text-secondary: #9ca3af;
    --vbp-text-muted: #6b7280;
    --vbp-border-color: #374151;
    --vbp-accent-color: #60a5fa;
    --vbp-accent-hover: #93c5fd;
    --vbp-success: #10b981;
    --vbp-warning: #f59e0b;
    --vbp-error: #ef4444;
    --vbp-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
}
```

## Crear Tema Personalizado

### Via UI

1. Ve a Configuracion > Apariencia
2. Click en "Crear tema personalizado"
3. Ajusta los colores
4. Guarda con un nombre

### Via Codigo

```javascript
VBPEditorThemes.registerTheme({
    id: 'mi-tema',
    name: 'Mi Tema',
    colors: {
        bgPrimary: '#1a1a2e',
        bgSecondary: '#16213e',
        bgTertiary: '#0f3460',
        textPrimary: '#e94560',
        textSecondary: '#ffffff',
        textMuted: '#9ca3af',
        borderColor: '#0f3460',
        accentColor: '#e94560',
        accentHover: '#ff6b6b',
        success: '#00bf63',
        warning: '#ffc93c',
        error: '#ff1e56'
    }
});

// Activar
VBPEditorThemes.setTheme('mi-tema');
```

### Exportar/Importar

```javascript
// Exportar tema
const themeJSON = VBPEditorThemes.export('mi-tema');

// Importar tema
VBPEditorThemes.import(themeJSON);
```

## API JavaScript

### VBPEditorThemes

```javascript
const themes = window.VBPEditorThemes;

// Temas
themes.setTheme(themeId);           // Establecer tema
themes.getTheme();                  // Obtener actual
themes.getThemes();                 // Listar todos
themes.toggleDarkMode();            // Toggle dark/light

// Registro
themes.registerTheme(config);       // Registrar tema
themes.unregisterTheme(themeId);    // Eliminar tema
themes.isRegistered(themeId);       // Verificar

// Exportar/Importar
themes.export(themeId);             // Exportar como JSON
themes.import(json);                // Importar desde JSON

// Personalizacion temporal
themes.setVariable(name, value);    // Cambiar variable
themes.getVariable(name);           // Obtener variable
themes.resetVariables();            // Resetear

// Preferencias
themes.getPreferences();            // Obtener preferencias
themes.setPreferences(prefs);       // Guardar preferencias
```

### Eventos

```javascript
// Tema cambiado
document.addEventListener('vbp:theme:changed', (e) => {
    console.log('Nuevo tema:', e.detail.theme);
    console.log('Anterior:', e.detail.previousTheme);
});

// Preferencia de sistema cambiada
window.matchMedia('(prefers-color-scheme: dark)').addListener((e) => {
    if (themes.getTheme() === 'auto') {
        themes.applySystemPreference();
    }
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Shift+T` | Toggle tema claro/oscuro |
| `Ctrl+,` | Abrir configuracion |

## Elementos del Editor

### Componentes Tematizados

| Componente | Variables |
|------------|-----------|
| Canvas | --vbp-canvas-bg |
| Toolbar | --vbp-toolbar-bg |
| Sidebar | --vbp-sidebar-bg |
| Inspector | --vbp-inspector-bg |
| Modal | --vbp-modal-bg |
| Dropdown | --vbp-dropdown-bg |
| Tooltip | --vbp-tooltip-bg |
| Button | --vbp-btn-bg, --vbp-btn-text |

### Personalizar Componentes

```javascript
// Cambiar solo un componente
VBPEditorThemes.setVariable('--vbp-canvas-bg', '#f0f0f0');

// Multiples variables
VBPEditorThemes.setVariables({
    '--vbp-canvas-bg': '#f0f0f0',
    '--vbp-toolbar-bg': '#ffffff'
});
```

## Persistencia

### Local Storage

El tema seleccionado se guarda en localStorage:

```javascript
// Se guarda automaticamente
localStorage.getItem('vbp-editor-theme');  // 'dark'
```

### Preferencia de Usuario

```php
// WordPress user meta
update_user_meta($user_id, 'vbp_editor_theme', 'dark');
```

## Accesibilidad

### Recomendaciones

1. **Contraste**: Minimo 4.5:1 para texto normal
2. **Texto grande**: Minimo 3:1 para texto > 18px
3. **Focus visible**: Siempre visible el foco
4. **Colores**: No depender solo del color para informacion

### Verificar Contraste

```javascript
// Herramienta integrada
VBPEditorThemes.checkContrast();
// Muestra advertencias si hay problemas de contraste
```

## Temas por Defecto Adicionales

### Sepia

Tonos calidos para menos fatiga visual.

```javascript
themes.registerTheme({
    id: 'sepia',
    name: 'Sepia',
    colors: {
        bgPrimary: '#f4ecd8',
        bgSecondary: '#e8dcc8',
        textPrimary: '#5c4b37',
        accentColor: '#a67c52'
    }
});
```

### Nord

Tema inspirado en la paleta Nord.

```javascript
themes.registerTheme({
    id: 'nord',
    name: 'Nord',
    colors: {
        bgPrimary: '#2e3440',
        bgSecondary: '#3b4252',
        textPrimary: '#eceff4',
        accentColor: '#88c0d0'
    }
});
```

## Solucionar Problemas

### El tema no cambia

1. Limpia cache del navegador
2. Verifica que no hay CSS personalizado conflictivo
3. Revisa la consola por errores

### Colores incorrectos

1. Verifica que el tema esta registrado
2. Comprueba especificidad CSS
3. Revisa que variables existen

### Tema auto no detecta sistema

1. Verifica permisos del navegador
2. Comprueba `prefers-color-scheme` en DevTools
3. Algunos navegadores no soportan la media query
