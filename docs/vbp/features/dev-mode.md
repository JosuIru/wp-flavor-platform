# Dev Mode (Modo Desarrollador)

Herramientas para desarrolladores: inspección de código, exportación y debugging.

## Qué es

Modo especial que muestra información técnica de los elementos:
- CSS generado
- Estructura HTML
- Propiedades en JSON
- Código exportable

## Cómo activar

1. Clic en icono `</>` en toolbar
2. O atajo: `Ctrl+Shift+D`

## Panel de Inspección

Al seleccionar un elemento, Dev Mode muestra:

### CSS
```css
.element-abc123 {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 24px;
    background: #ffffff;
    border-radius: 8px;
}
```

### HTML
```html
<div class="vbp-section" data-vbp-id="abc123">
    <div class="vbp-container">
        <h2 class="vbp-heading">Título</h2>
        <p class="vbp-text">Contenido...</p>
    </div>
</div>
```

### JSON (Estructura)
```json
{
    "id": "abc123",
    "type": "section",
    "props": {
        "padding": "24px",
        "background": "#ffffff"
    },
    "children": [...]
}
```

## Exportar código

### Opciones de exportación

| Formato | Descripción |
|---------|-------------|
| HTML + CSS | Código estático |
| React | Componentes JSX |
| Vue | Componentes .vue |
| Tailwind | Clases Tailwind CSS |
| WordPress | Bloque Gutenberg |

### Ejemplo React
```jsx
export const MiComponente = () => (
    <section className="flex flex-col gap-4 p-6 bg-white rounded-lg">
        <h2 className="text-2xl font-bold">Título</h2>
        <p className="text-gray-600">Contenido...</p>
    </section>
);
```

## Herramientas de debug

### Overlay de layout
Muestra:
- Bordes de todos los elementos
- Padding (verde)
- Margin (naranja)
- Dimensiones

### Console
Panel integrado que muestra:
- Eventos del editor
- Errores de renderizado
- Warnings de accesibilidad

## Medidas y especificaciones

Al hover sobre un elemento:
- Dimensiones exactas (px)
- Colores en HEX/RGB/HSL
- Tipografía (font, size, weight, line-height)
- Espaciados

## Atajos en Dev Mode

| Atajo | Acción |
|-------|--------|
| `Ctrl+Shift+D` | Toggle Dev Mode |
| `Ctrl+Shift+C` | Copiar CSS |
| `Ctrl+Shift+H` | Copiar HTML |
| `Alt+Click` | Inspeccionar elemento específico |

## Casos de uso

- **Handoff a desarrollo**: Exportar especificaciones
- **Debugging**: Encontrar problemas de layout
- **Aprendizaje**: Ver cómo se genera el código
- **Integración**: Exportar a otros frameworks
