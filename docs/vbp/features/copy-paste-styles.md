# Copy/Paste Styles (Copiar y Pegar Estilos)

Funcionalidad para copiar los estilos de un elemento y aplicarlos a otros, sin afectar el contenido.

## Descripcion

Permite transferir rapidamente estilos visuales (colores, tipografia, espaciado, bordes, sombras) entre elementos. Ideal para mantener consistencia en el diseno sin tener que configurar cada propiedad manualmente.

## Como Usar

### Copiar Estilos

1. Selecciona el elemento origen (el que tiene los estilos deseados)
2. Presiona `Ctrl+Alt+C`
3. Aparece una notificacion confirmando que los estilos se copiaron

### Pegar Estilos

1. Selecciona el elemento destino (o multiples elementos)
2. Presiona `Ctrl+Alt+V`
3. Los estilos se aplican inmediatamente

### Resetear Estilos

Para volver a los estilos por defecto:
- Presiona `Ctrl+Shift+R`
- O usa el menu contextual > "Resetear estilos"

## Estilos que se Copian

| Categoria | Propiedades |
|-----------|-------------|
| **Colores** | background, color, borderColor |
| **Tipografia** | fontSize, fontWeight, fontFamily, lineHeight, letterSpacing, textAlign |
| **Espaciado** | padding (top, right, bottom, left), margin (top, right, bottom, left) |
| **Bordes** | borderRadius, borderWidth, borderStyle, borderColor |
| **Sombras** | boxShadow, textShadow |
| **Efectos** | opacity, filter, transform |

## Estilos que NO se Copian

- Contenido (textos, imagenes, enlaces)
- Posicion (left, top, position)
- Dimensiones (width, height) - opcional
- IDs y clases CSS personalizadas
- Atributos de datos

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+C` | Copiar estilos |
| `Ctrl+Alt+V` | Pegar estilos |
| `Ctrl+Shift+R` | Resetear estilos |

## Opciones Avanzadas

### Pegar Solo Algunas Propiedades

Desde la paleta de comandos (`Ctrl+K`):

1. Escribe "Pegar estilos..."
2. Selecciona las categorias a pegar:
   - Solo colores
   - Solo tipografia
   - Solo espaciado
   - Solo bordes

### Copiar con Dimensiones

Por defecto no se copian las dimensiones. Para incluirlas:

1. Usa `Ctrl+Alt+Shift+C` para copiar con dimensiones
2. O configura en Ajustes > VBP > "Incluir dimensiones al copiar estilos"

## API JavaScript

### Copiar Estilos Programaticamente

```javascript
// Copiar estilos del elemento seleccionado
const store = Alpine.store('vbp');
store.copyStyles();

// Copiar estilos de un elemento especifico
store.copyStylesFromElement(elementId);
```

### Pegar Estilos

```javascript
// Pegar en seleccion actual
store.pasteStyles();

// Pegar en elementos especificos
store.pasteStylesToElements([id1, id2, id3]);

// Pegar solo ciertas categorias
store.pasteStyles(['colors', 'typography']);
```

### Acceder al Clipboard de Estilos

```javascript
// Obtener estilos copiados
const copiedStyles = store.getClipboardStyles();

// Ejemplo de estructura:
{
    colors: {
        background: '#ffffff',
        text: '#1f2937'
    },
    typography: {
        fontSize: '16px',
        fontWeight: '400',
        fontFamily: 'Inter, sans-serif'
    },
    spacing: {
        padding: { top: '16px', right: '24px', bottom: '16px', left: '24px' },
        margin: { top: '0', right: '0', bottom: '24px', left: '0' }
    },
    borders: {
        radius: '8px',
        width: '1px',
        style: 'solid',
        color: '#e5e7eb'
    },
    shadows: {
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
    }
}
```

### Eventos

```javascript
// Cuando se copian estilos
document.addEventListener('vbp:styles:copied', (event) => {
    console.log('Estilos copiados:', event.detail.styles);
});

// Cuando se pegan estilos
document.addEventListener('vbp:styles:pasted', (event) => {
    console.log('Estilos pegados a:', event.detail.elementIds);
});
```

## Persistencia

Los estilos copiados se almacenan en:

1. **Sesion actual**: Disponibles mientras el editor este abierto
2. **localStorage**: Persisten entre sesiones del mismo navegador

Para limpiar el clipboard:

```javascript
store.clearStylesClipboard();
```

## Uso con Seleccion Multiple

Cuando hay multiples elementos seleccionados:

### Copiar
- Se copian los estilos del **primer elemento** de la seleccion

### Pegar
- Se aplican a **todos** los elementos seleccionados
- Util para uniformizar rapidamente varios elementos

## Integracion con Historial

Las operaciones de pegar estilos:
- Se registran en el historial de Undo/Redo
- `Ctrl+Z` deshace el pegado completo
- Cada elemento se revierte a sus estilos anteriores

## Casos de Uso

### Uniformizar Botones

1. Disena un boton con los estilos deseados
2. Copia sus estilos (`Ctrl+Alt+C`)
3. Selecciona todos los botones (`Ctrl+A` en contenedor)
4. Pega estilos (`Ctrl+Alt+V`)

### Crear Variantes

1. Copia estilos del elemento base
2. Crea un nuevo elemento
3. Pega estilos
4. Modifica solo los colores

### Transferir Entre Paginas

1. Copia estilos (persisten en localStorage)
2. Cambia de pagina
3. Selecciona elementos destino
4. Pega estilos

## Consideraciones

- El clipboard de estilos es independiente del clipboard del sistema
- No interfiere con Ctrl+C/Ctrl+V normal
- Los estilos responsive se copian solo para el breakpoint activo
- Las referencias a Global Styles se copian como valores, no como referencias
