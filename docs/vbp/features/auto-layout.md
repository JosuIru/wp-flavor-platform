# Auto Layout

Sistema de layout automático similar a Figma para organizar elementos de forma dinámica.

## Qué es

Auto Layout convierte cualquier contenedor en un layout flexible que:
- Distribuye hijos automáticamente (horizontal/vertical)
- Ajusta espaciado uniforme
- Redimensiona según contenido
- Responde a cambios dinámicamente

## Cómo activar

1. Seleccionar un contenedor
2. En Inspector → Layout → Activar "Auto Layout"
3. O usar atajo: `Shift+A`

## Propiedades

| Propiedad | Descripción | Valores |
|-----------|-------------|---------|
| `direction` | Dirección del flujo | `horizontal`, `vertical` |
| `gap` | Espacio entre elementos | `0-100px` |
| `padding` | Relleno interno | `0-100px` (uniforme o por lado) |
| `align` | Alineación cruzada | `start`, `center`, `end`, `stretch` |
| `justify` | Distribución principal | `start`, `center`, `end`, `space-between` |
| `wrap` | Permitir salto de línea | `true`, `false` |

## Ejemplos de uso

### Barra de navegación
```
Direction: horizontal
Gap: 16px
Align: center
Justify: space-between
```

### Lista de cards
```
Direction: horizontal
Gap: 24px
Wrap: true
```

### Formulario vertical
```
Direction: vertical
Gap: 12px
Align: stretch
```

## Atajos de teclado

| Atajo | Acción |
|-------|--------|
| `Shift+A` | Activar/desactivar Auto Layout |
| `Alt+H` | Cambiar a horizontal |
| `Alt+V` | Cambiar a vertical |

## Diferencias con CSS Flexbox

Auto Layout es una abstracción visual de Flexbox:

| Auto Layout | CSS Equivalente |
|-------------|-----------------|
| direction: horizontal | flex-direction: row |
| direction: vertical | flex-direction: column |
| gap: 16px | gap: 16px |
| align: center | align-items: center |
| justify: space-between | justify-content: space-between |

## Casos de uso

- Navbars y menús
- Grids de productos
- Formularios
- Cards con contenido variable
- Layouts responsivos
