# Design Tokens

Sistema de tokens de diseno para mantener consistencia visual y facilitar tematizacion.

## Descripcion

Los Design Tokens son valores de diseno (colores, tipografia, espaciado, etc.) definidos como variables reutilizables. Permiten mantener consistencia, facilitar cambios globales, y soportar temas/modos.

## Como Acceder

- Atajo: `Ctrl+Alt+Shift+T`
- Panel lateral: Tokens
- Inspector: Cualquier propiedad con icono de token

## Tipos de Tokens

### Colores

```javascript
// Tokens de color
{
    // Primitivos
    "color.blue.500": "#3b82f6",
    "color.blue.600": "#2563eb",
    "color.green.500": "#10b981",

    // Semanticos
    "color.primary": "{color.blue.500}",
    "color.primary.hover": "{color.blue.600}",
    "color.success": "{color.green.500}",
    "color.error": "#ef4444",

    // Componentes
    "color.button.background": "{color.primary}",
    "color.button.text": "#ffffff"
}
```

### Tipografia

```javascript
{
    // Font families
    "font.family.sans": "Inter, system-ui, sans-serif",
    "font.family.mono": "JetBrains Mono, monospace",

    // Font sizes
    "font.size.xs": "0.75rem",
    "font.size.sm": "0.875rem",
    "font.size.base": "1rem",
    "font.size.lg": "1.125rem",
    "font.size.xl": "1.25rem",
    "font.size.2xl": "1.5rem",

    // Font weights
    "font.weight.normal": "400",
    "font.weight.medium": "500",
    "font.weight.bold": "700",

    // Line heights
    "line.height.tight": "1.25",
    "line.height.normal": "1.5",
    "line.height.relaxed": "1.75"
}
```

### Espaciado

```javascript
{
    // Scale
    "spacing.0": "0",
    "spacing.1": "0.25rem",    // 4px
    "spacing.2": "0.5rem",     // 8px
    "spacing.3": "0.75rem",    // 12px
    "spacing.4": "1rem",       // 16px
    "spacing.6": "1.5rem",     // 24px
    "spacing.8": "2rem",       // 32px

    // Semanticos
    "spacing.xs": "{spacing.1}",
    "spacing.sm": "{spacing.2}",
    "spacing.md": "{spacing.4}",
    "spacing.lg": "{spacing.6}",
    "spacing.xl": "{spacing.8}"
}
```

### Bordes

```javascript
{
    // Radius
    "border.radius.none": "0",
    "border.radius.sm": "0.125rem",
    "border.radius.md": "0.375rem",
    "border.radius.lg": "0.5rem",
    "border.radius.full": "9999px",

    // Width
    "border.width.thin": "1px",
    "border.width.medium": "2px",
    "border.width.thick": "4px"
}
```

### Sombras

```javascript
{
    "shadow.sm": "0 1px 2px 0 rgb(0 0 0 / 0.05)",
    "shadow.md": "0 4px 6px -1px rgb(0 0 0 / 0.1)",
    "shadow.lg": "0 10px 15px -3px rgb(0 0 0 / 0.1)",
    "shadow.xl": "0 20px 25px -5px rgb(0 0 0 / 0.1)"
}
```

### Animaciones

```javascript
{
    // Duraciones
    "duration.fast": "150ms",
    "duration.normal": "300ms",
    "duration.slow": "500ms",

    // Easing
    "easing.ease": "cubic-bezier(0.4, 0, 0.2, 1)",
    "easing.easeIn": "cubic-bezier(0.4, 0, 1, 1)",
    "easing.easeOut": "cubic-bezier(0, 0, 0.2, 1)"
}
```

## Referencias (Aliases)

Los tokens pueden referenciar otros tokens:

```javascript
{
    // Token primitivo
    "color.blue.500": "#3b82f6",

    // Token que referencia
    "color.primary": "{color.blue.500}",

    // Token que referencia otro alias
    "button.background": "{color.primary}"
}
```

## Modos (Themes)

Los tokens pueden tener valores diferentes por modo:

```javascript
{
    "color.background": {
        "light": "#ffffff",
        "dark": "#111827"
    },
    "color.text": {
        "light": "#1f2937",
        "dark": "#f9fafb"
    }
}
```

## API JavaScript

### VBPDesignTokens

```javascript
const tokens = window.VBPDesignTokens;

// Obtener valor
const color = tokens.get('color.primary');
const resolved = tokens.resolve('color.primary');  // Resuelve referencias

// Establecer valor
tokens.set('color.primary', '#ff0000');

// Crear token
tokens.create({
    name: 'color.custom',
    value: '#ff0000',
    type: 'color',
    description: 'Color personalizado'
});

// Eliminar token
tokens.delete('color.custom');

// Listar tokens
const all = tokens.list();
const colors = tokens.listByType('color');
const group = tokens.listByGroup('button');

// Modos
tokens.setMode('dark');
tokens.getMode();
tokens.listModes();

// Importar/Exportar
const json = tokens.export();
tokens.import(json);
tokens.importFromFigma(figmaTokens);
tokens.importFromStyleDictionary(sdTokens);

// CSS
const css = tokens.toCSS();
tokens.injectCSS();
```

### En el Inspector

Cualquier campo que soporte tokens muestra un icono de token:

1. Click en el icono
2. Selecciona de la lista o busca
3. El valor se vincula al token

```javascript
// Verificar si una propiedad usa token
const usesToken = tokens.isTokenValue(value);

// Obtener nombre del token de un valor
const tokenName = tokens.getTokenName('{color.primary}');
```

## Eventos

```javascript
// Token cambiado
document.addEventListener('vbp:token:changed', (e) => {
    console.log('Token:', e.detail.name);
    console.log('Valor:', e.detail.value);
});

// Modo cambiado
document.addEventListener('vbp:tokens:mode:changed', (e) => {
    console.log('Nuevo modo:', e.detail.mode);
});

// Tokens importados
document.addEventListener('vbp:tokens:imported', (e) => {
    console.log('Tokens importados:', e.detail.count);
});
```

## Sincronizacion con Figma

### Importar desde Figma

```javascript
// Importar tokens de Figma
const figmaTokens = {
    "color": {
        "primary": { "value": "#3b82f6", "type": "color" },
        "secondary": { "value": "#10b981", "type": "color" }
    }
};

tokens.importFromFigma(figmaTokens);
```

### Exportar para Figma

```javascript
// Exportar en formato Figma
const forFigma = tokens.exportForFigma();
```

## Style Dictionary

### Formato Compatible

```javascript
// Importar formato Style Dictionary
const sdTokens = {
    "color": {
        "base": {
            "blue": {
                "value": "#3b82f6"
            }
        }
    }
};

tokens.importFromStyleDictionary(sdTokens);
```

### Exportar para Style Dictionary

```javascript
// Exportar para SD
const forSD = tokens.exportForStyleDictionary();
```

## Panel de Tokens

### Interfaz

```
DESIGN TOKENS
├── Colores
│   ├── Primitivos (12)
│   │   ├── color.blue.500: #3b82f6
│   │   ├── color.blue.600: #2563eb
│   │   └── ...
│   ├── Semanticos (8)
│   │   ├── color.primary: {color.blue.500}
│   │   └── ...
│   └── Componentes (5)
│       └── ...
├── Tipografia
│   └── ...
├── Espaciado
│   └── ...
└── [+ Crear Token]
```

### Filtros y Busqueda

- Filtrar por tipo (color, spacing, etc.)
- Filtrar por grupo
- Busqueda por nombre

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+Shift+T` | Abrir panel de tokens |
| `Ctrl+Shift+T` | Buscar token |

## Generacion de CSS

### Variables CSS

```javascript
const css = tokens.toCSS();
// Genera:
// :root {
//     --color-primary: #3b82f6;
//     --color-secondary: #10b981;
//     --spacing-md: 1rem;
// }
```

### Con Modos

```javascript
const css = tokens.toCSS({ includeModes: true });
// Genera:
// :root {
//     --color-background: #ffffff;
// }
// [data-theme="dark"] {
//     --color-background: #111827;
// }
```

### Tailwind Config

```javascript
// Exportar para Tailwind
const tailwindConfig = tokens.toTailwind();
// Genera:
// {
//     colors: {
//         primary: 'var(--color-primary)',
//         ...
//     }
// }
```

## Validacion

```javascript
// Validar tokens
const validation = tokens.validate();

// Estructura
{
    valid: true,
    errors: [],
    warnings: [
        { token: 'color.old', message: 'Token no usado' }
    ]
}

// Encontrar tokens no usados
const unused = tokens.findUnused();

// Encontrar referencias rotas
const broken = tokens.findBrokenReferences();
```

## Consideraciones

### Nomenclatura

Recomendaciones:
- Usar kebab-case o dot notation
- Agrupar por tipo: `color.primary`, `spacing.md`
- Niveles: primitivo > semantico > componente

### Performance

- Los tokens se resuelven en tiempo de compilacion
- Cambios se propagan via CSS variables
- Evitar demasiados niveles de referencia

## Solucionar Problemas

### El token no se aplica

1. Verifica que el nombre es correcto
2. Comprueba que el token existe
3. Revisa referencias circulares
4. Verifica que CSS esta inyectado

### Referencia rota

1. El token referenciado no existe
2. Usa `tokens.findBrokenReferences()` para detectar
3. Corrige o elimina la referencia

### El modo no cambia

1. Verifica que el token tiene valor para ese modo
2. Comprueba que el modo existe
3. Revisa que CSS soporta el atributo de modo
