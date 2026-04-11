# Bulk Edit (Edicion Masiva)

Sistema que permite editar propiedades comunes de multiples elementos seleccionados simultaneamente.

## Descripcion

Cuando seleccionas varios elementos, el Inspector muestra las propiedades que comparten. Los campos con valores diferentes muestran "Mixed" (mixto). Al editar una propiedad, el cambio se aplica a todos los elementos seleccionados.

## Como Usar

### Seleccionar Multiples Elementos

**Opcion 1: Shift+Click**
1. Selecciona el primer elemento
2. Mantiene `Shift` y haz clic en otros elementos

**Opcion 2: Ctrl/Cmd+Click**
1. Mantiene `Ctrl` (o `Cmd` en Mac)
2. Haz clic en cada elemento que quieras agregar

**Opcion 3: Seleccion por Area**
1. Haz clic en un area vacia del canvas
2. Arrastra para crear un rectangulo de seleccion
3. Los elementos dentro se seleccionan

**Opcion 4: Seleccionar Todo**
- `Ctrl+A` selecciona todos los elementos del nivel actual

### Editar Propiedades

1. Con multiples elementos seleccionados, abre el Inspector
2. Los campos muestran:
   - **Valor comun**: Si todos tienen el mismo valor
   - **"Mixed"**: Si los valores difieren
3. Edita cualquier campo
4. El cambio se aplica a todos los elementos

## Propiedades Soportadas

### Colores
- Background color
- Text color
- Border color

### Tipografia
- Font size
- Font weight
- Font family
- Text align
- Line height
- Letter spacing

### Espaciado
- Padding (top, right, bottom, left)
- Margin (top, right, bottom, left)

### Bordes
- Border radius
- Border width
- Border style
- Border color

### Layout
- Display
- Flex direction
- Justify content
- Align items
- Gap

### Avanzado
- CSS classes

## Indicador Visual

Cuando estas en modo Bulk Edit:

- El Inspector muestra un badge "N elementos seleccionados"
- Los campos con valores mixtos tienen un icono especial
- Un checkbox permite "Aplicar solo a seleccionados"

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Shift+Click` | Agregar a seleccion |
| `Ctrl+Click` | Toggle seleccion |
| `Ctrl+A` | Seleccionar todo |
| `Ctrl+Shift+A` | Invertir seleccion |
| `Ctrl+Alt+A` | Seleccionar similares |
| `Escape` | Deseleccionar todo |

## API JavaScript

### Detectar Modo Bulk

```javascript
const bulkEdit = window.VBPBulkEdit;

// Verificar si esta activo
if (bulkEdit.isActive) {
    console.log('Elementos seleccionados:', bulkEdit.selectedIds);
}
```

### Obtener Valores Comunes

```javascript
// Obtener valores que comparten los elementos seleccionados
const commonValues = bulkEdit.getCommonValues();

// Ejemplo de resultado:
{
    'colors.background': '#ffffff',        // Mismo valor en todos
    'colors.text': '__VBP_MIXED__',        // Valores diferentes
    'typography.fontSize': '16px',
    'spacing.padding.top': '__VBP_MIXED__'
}
```

### Aplicar Cambio Masivo

```javascript
// Aplicar un estilo a todos los seleccionados
bulkEdit.applyToAll('colors.background', '#f3f4f6');

// Aplicar multiples estilos
bulkEdit.applyMultiple({
    'colors.background': '#f3f4f6',
    'typography.fontSize': '18px',
    'spacing.padding.top': '24px'
});
```

### Propiedades Soportadas

```javascript
// Lista de paths de propiedades soportadas
console.log(bulkEdit.supportedStylePaths);

// Ejemplo:
[
    'colors.background',
    'colors.text',
    'typography.fontSize',
    'typography.fontWeight',
    'spacing.padding.top',
    // ... etc
]
```

### Eventos

```javascript
// Cuando cambia la seleccion
document.addEventListener('vbp:selection-changed', (event) => {
    if (event.detail.elementIds.length > 1) {
        console.log('Modo Bulk activo');
    }
});

// Cuando se aplica un cambio masivo
document.addEventListener('vbp:bulk:applied', (event) => {
    console.log('Propiedad:', event.detail.property);
    console.log('Valor:', event.detail.value);
    console.log('Elementos afectados:', event.detail.elementIds);
});
```

## Valor Especial "Mixed"

El sistema usa un valor centinela para indicar valores mixtos:

```javascript
const MIXED_VALUE = '__VBP_MIXED__';

// Verificar si un valor es mixto
if (valor === VBPBulkEdit.MIXED_VALUE) {
    // Mostrar placeholder "Mixed"
}
```

### Comportamiento del Input

Cuando un campo tiene valor "Mixed":

1. **Placeholder**: Muestra "Valores mixtos" o "Mixed"
2. **Sin valor**: El input esta vacio
3. **Al escribir**: El nuevo valor reemplaza todos los valores

## Integracion con Historial

Las operaciones de Bulk Edit:

- Se registran como una sola accion en el historial
- `Ctrl+Z` deshace el cambio en todos los elementos
- Cada elemento se revierte a su valor anterior

## Casos de Uso

### Uniformizar Colores

1. Selecciona todos los headings (`Ctrl+Alt+A` despues de seleccionar uno)
2. Cambia el color de texto
3. Todos los headings se actualizan

### Ajustar Espaciado

1. Selecciona multiples secciones
2. Ajusta el padding
3. Todas las secciones tienen el mismo espaciado

### Cambiar Tipografia

1. Selecciona elementos de texto
2. Cambia font-size o font-weight
3. El cambio se aplica a todos

### Alinear Estilos

1. Selecciona elementos con estilos inconsistentes
2. Los campos "Mixed" indican donde hay diferencias
3. Unifica los valores necesarios

## Consideraciones

- No todos los tipos de elementos comparten las mismas propiedades
- Propiedades no soportadas no aparecen en Bulk Edit
- Las propiedades de contenido (textos, imagenes) no se editan en masa
- Los cambios se aplican al breakpoint activo (responsive)

## Solucionar Problemas

### No aparecen todas las propiedades

1. Verifica que los elementos seleccionados comparten ese tipo de propiedad
2. Revisa que la propiedad esta en `supportedStylePaths`

### Los cambios no se aplican

1. Verifica que el valor es valido para esa propiedad
2. Comprueba la consola por errores de validacion
3. Asegurate de que los elementos no estan bloqueados

### El modo Bulk no se activa

1. Verifica que hay mas de un elemento seleccionado
2. Comprueba que los elementos son editables (no bloqueados)
3. Revisa la consola por errores
