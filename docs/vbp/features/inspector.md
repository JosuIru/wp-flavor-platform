# Inspector

Panel lateral derecho que muestra y permite editar las propiedades del elemento seleccionado.

## Descripcion

El Inspector es el panel contextual que muestra todas las propiedades editables del elemento seleccionado. Organizado en secciones colapsables, permite modificar contenido, estilos, layout y opciones avanzadas.

## Como Acceder

- Atajo: `Ctrl+I` (toggle)
- Menu: View > Inspector
- Siempre visible por defecto en el panel derecho

## Secciones del Inspector

### 1. Contenido

Campos especificos del tipo de bloque:

| Bloque | Campos |
|--------|--------|
| Heading | Texto, nivel (h1-h6) |
| Text | Contenido, formato |
| Image | URL, alt, tamano |
| Button | Texto, URL, icono |
| Video | URL, autoplay, controles |

### 2. Estilos

#### Tipografia

| Propiedad | Descripcion |
|-----------|-------------|
| Font Family | Fuente tipografica |
| Font Size | Tamano de texto |
| Font Weight | Grosor de texto |
| Line Height | Altura de linea |
| Letter Spacing | Espaciado entre letras |
| Text Align | Alineacion de texto |
| Text Color | Color de texto |

#### Colores

| Propiedad | Descripcion |
|-----------|-------------|
| Background | Color o gradiente de fondo |
| Text Color | Color de texto |
| Border Color | Color de borde |

#### Espaciado

| Propiedad | Descripcion |
|-----------|-------------|
| Margin | Margen exterior |
| Padding | Espaciado interior |
| Gap | Espacio entre hijos (flex/grid) |

#### Bordes

| Propiedad | Descripcion |
|-----------|-------------|
| Border Width | Grosor del borde |
| Border Style | Estilo (solid, dashed, etc) |
| Border Color | Color del borde |
| Border Radius | Radio de esquinas |

### 3. Layout

#### Display

| Opcion | Descripcion |
|--------|-------------|
| Block | Display block |
| Flex | Flexbox container |
| Grid | CSS Grid container |
| Inline | Display inline |
| None | Oculto |

#### Flexbox

| Propiedad | Descripcion |
|-----------|-------------|
| Direction | Row, column |
| Justify | Alineacion principal |
| Align | Alineacion cruzada |
| Wrap | Wrap de items |
| Gap | Espacio entre items |

#### Grid

| Propiedad | Descripcion |
|-----------|-------------|
| Columns | Definicion de columnas |
| Rows | Definicion de filas |
| Gap | Espacio entre celdas |

#### Posicion

| Propiedad | Descripcion |
|-----------|-------------|
| Position | Static, relative, absolute, fixed |
| Top/Right/Bottom/Left | Offsets |
| Z-Index | Capa de apilamiento |

### 4. Efectos

| Propiedad | Descripcion |
|-----------|-------------|
| Box Shadow | Sombras |
| Opacity | Transparencia |
| Filter | Blur, brightness, etc |
| Transform | Rotate, scale, translate |
| Transition | Animaciones de transicion |

### 5. Avanzado

| Campo | Descripcion |
|-------|-------------|
| CSS Classes | Clases adicionales |
| ID | Identificador unico |
| Custom CSS | CSS personalizado |
| Attributes | Atributos HTML custom |
| Visibilidad | Ocultar en breakpoints |

## Configuracion

### Mostrar/Ocultar Secciones

```javascript
// Acceder al inspector
const inspector = window.VBPInspector;

// Colapsar seccion
inspector.collapseSection('typography');

// Expandir seccion
inspector.expandSection('layout');

// Toggle
inspector.toggleSection('effects');
```

### Personalizar Campos

```javascript
// Agregar campo personalizado a un bloque
Alpine.store('vbp').registerInspectorField('mi-bloque', {
    name: 'customField',
    label: 'Mi Campo',
    type: 'text',
    default: '',
    section: 'content'
});
```

## API JavaScript

### VBPInspector

```javascript
const inspector = window.VBPInspector;

// Obtener valores
inspector.getValue(elementId, 'backgroundColor');
inspector.getStyles(elementId);
inspector.getAllProperties(elementId);

// Establecer valores
inspector.setValue(elementId, 'backgroundColor', '#ff0000');
inspector.setStyles(elementId, {
    backgroundColor: '#ff0000',
    padding: '20px'
});

// Secciones
inspector.collapseSection(sectionName);
inspector.expandSection(sectionName);
inspector.toggleSection(sectionName);

// Tabs
inspector.setTab('styles');    // 'content', 'styles', 'layout', 'advanced'
inspector.getActiveTab();

// Refresh
inspector.refresh();           // Actualizar con elemento seleccionado
inspector.clear();             // Limpiar (ninguna seleccion)
```

### Integracion con Store

```javascript
const store = Alpine.store('vbp');

// El inspector se actualiza automaticamente al cambiar seleccion
store.select(elementId);

// Actualizar elemento (dispara refresh de inspector)
store.updateElement(elementId, {
    styles: {
        backgroundColor: '#ff0000'
    }
});
```

### Eventos

```javascript
// Valor cambiado
document.addEventListener('vbp:inspector:value:changed', (e) => {
    console.log('Propiedad:', e.detail.property);
    console.log('Valor:', e.detail.value);
    console.log('Elemento:', e.detail.elementId);
});

// Seccion expandida/colapsada
document.addEventListener('vbp:inspector:section:toggled', (e) => {
    console.log('Seccion:', e.detail.section);
    console.log('Expandida:', e.detail.expanded);
});

// Tab cambiado
document.addEventListener('vbp:inspector:tab:changed', (e) => {
    console.log('Nuevo tab:', e.detail.tab);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+I` | Toggle inspector |
| `Ctrl+Shift+T` | Editor de tipografia |
| `Ctrl+Shift+B` | Editor de bordes |
| `Ctrl+Alt+P` | Editor de espaciado |
| `Ctrl+Alt+Shift+S` | Editor de sombras |
| `Ctrl+Alt+Shift+X` | Editor de gradientes |
| `Ctrl+Alt+Shift+H` | Editor de estados hover |

## Tipos de Campos

### text

```javascript
{
    type: 'text',
    label: 'Titulo',
    placeholder: 'Escribe aqui...'
}
```

### textarea

```javascript
{
    type: 'textarea',
    label: 'Descripcion',
    rows: 4
}
```

### number

```javascript
{
    type: 'number',
    label: 'Tamano',
    min: 0,
    max: 100,
    step: 1,
    unit: 'px'
}
```

### select

```javascript
{
    type: 'select',
    label: 'Alineacion',
    options: [
        { value: 'left', label: 'Izquierda' },
        { value: 'center', label: 'Centro' },
        { value: 'right', label: 'Derecha' }
    ]
}
```

### color

```javascript
{
    type: 'color',
    label: 'Color de fondo',
    alpha: true    // Soporta transparencia
}
```

### spacing

```javascript
{
    type: 'spacing',
    label: 'Margin',
    properties: ['marginTop', 'marginRight', 'marginBottom', 'marginLeft'],
    linked: true   // Valores enlazados por defecto
}
```

### media

```javascript
{
    type: 'media',
    label: 'Imagen',
    mediaType: 'image',    // 'image', 'video', 'audio'
    preview: true
}
```

## Seleccion Multiple (Bulk Edit)

Cuando hay multiples elementos seleccionados:

- Campos con valores iguales muestran el valor
- Campos con valores diferentes muestran "Mixed"
- Editar aplica a todos los seleccionados

```javascript
// Verificar si hay edicion masiva activa
const bulkEdit = window.VBPBulkEdit;

if (bulkEdit.isActive) {
    console.log('Elementos seleccionados:', bulkEdit.selectedIds);
    console.log('Valores comunes:', bulkEdit.getCommonValues());
}
```

## Solucionar Problemas

### El inspector no muestra propiedades

1. Verifica que hay un elemento seleccionado
2. Comprueba que el bloque tiene propiedades definidas
3. Revisa la consola por errores

### Los cambios no se aplican

1. Verifica que el elemento no esta bloqueado
2. Comprueba que el valor es valido
3. Revisa que no hay conflictos de especificidad CSS

### Las secciones no se expanden

1. Refresca la pagina
2. Limpia localStorage
3. Verifica errores en consola
