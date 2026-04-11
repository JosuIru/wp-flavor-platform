# Global Styles (Estilos Globales)

Sistema de clases CSS reutilizables que se pueden aplicar a elementos y se actualizan globalmente en todas las instancias.

## Descripcion

Los Global Styles permiten definir conjuntos de propiedades CSS (como `.heading-1`, `.button-primary`) que se aplican a elementos. Cuando modificas un estilo global, todos los elementos que lo usan se actualizan automaticamente.

## Diferencia con Design Tokens

| Concepto | Descripcion | Ejemplo |
|----------|-------------|---------|
| **Design Tokens** | Variables CSS individuales | `--color-primary: #3b82f6` |
| **Global Styles** | Clases completas que combinan propiedades | `.button-primary { background: var(--color-primary); padding: 12px 24px; ... }` |

## Como Usar

### Crear un Global Style

1. Selecciona un elemento con los estilos que quieres guardar
2. En el Inspector, haz click en "Guardar como estilo global"
3. Asigna un nombre (ej: `heading-1`)
4. Opcionalmente elige una categoria

### Aplicar un Global Style

1. Selecciona un elemento
2. En el Inspector, abre "Estilos Globales"
3. Selecciona el estilo deseado
4. El estilo se aplica al elemento

### Editar un Global Style

1. Abre el panel de Global Styles
2. Selecciona el estilo a editar
3. Modifica las propiedades
4. Guarda los cambios
5. Todos los elementos que usan ese estilo se actualizan

### Desvincular un Elemento

Para que un elemento deje de usar un estilo global:

1. Selecciona el elemento
2. En el Inspector, haz click en "Desvincular estilo"
3. El elemento conserva los valores pero ya no esta vinculado

## Categorias de Estilos

### Predefinidas

| Categoria | Uso |
|-----------|-----|
| **typography** | Estilos de texto (headings, body, captions) |
| **buttons** | Estilos de botones |
| **cards** | Estilos de tarjetas |
| **forms** | Inputs, selects, labels |
| **layout** | Containers, grids, spacings |

### Crear Categoria

```javascript
VBPGlobalStyles.createCategory({
    id: 'mi-categoria',
    name: 'Mi Categoria',
    icon: 'folder'
});
```

## Estructura de un Global Style

```json
{
    "id": "heading-1",
    "name": "Heading 1",
    "category": "typography",
    "properties": {
        "fontSize": "48px",
        "fontWeight": "700",
        "lineHeight": "1.2",
        "color": "var(--color-text)",
        "marginBottom": "24px"
    },
    "usageCount": 12
}
```

## API JavaScript

### Obtener Estilos

```javascript
// Todos los estilos
VBPGlobalStyles.getAll().then(function(styles) {
    console.log(styles);
});

// Agrupados por categoria
VBPGlobalStyles.getGrouped().then(function(categories) {
    console.log(categories);
});

// Un estilo especifico
VBPGlobalStyles.get('heading-1').then(function(style) {
    console.log(style);
});
```

### Crear Estilo

```javascript
VBPGlobalStyles.create({
    name: 'Button Primary',
    category: 'buttons',
    properties: {
        backgroundColor: '#3b82f6',
        color: '#ffffff',
        padding: '12px 24px',
        borderRadius: '8px',
        fontWeight: '600'
    }
}).then(function(style) {
    console.log('Creado:', style);
});
```

### Actualizar Estilo

```javascript
VBPGlobalStyles.update('button-primary', {
    properties: {
        backgroundColor: '#2563eb' // Nuevo color
    }
}).then(function(style) {
    console.log('Actualizado:', style);
});
```

### Eliminar Estilo

```javascript
VBPGlobalStyles.delete('mi-estilo').then(function() {
    console.log('Eliminado');
});
```

### Aplicar a Elemento

```javascript
VBPGlobalStyles.applyToElement(elementId, 'button-primary');
```

### Desvincular de Elemento

```javascript
VBPGlobalStyles.detachFromElement(elementId);
```

### Obtener Uso

```javascript
// Elementos que usan un estilo
VBPGlobalStyles.getUsage('heading-1').then(function(elements) {
    console.log('Usado en:', elements);
});
```

### Refrescar CSS

```javascript
// Regenerar CSS de estilos globales
VBPGlobalStyles.refreshCSS();
```

## API REST

### Listar Estilos

```http
GET /wp-json/flavor-vbp/v1/global-styles
```

### Obtener Agrupados

```http
GET /wp-json/flavor-vbp/v1/global-styles?group=1
```

### Crear Estilo

```http
POST /wp-json/flavor-vbp/v1/global-styles
Content-Type: application/json

{
    "name": "Mi Estilo",
    "category": "custom",
    "properties": {...}
}
```

### Actualizar Estilo

```http
PUT /wp-json/flavor-vbp/v1/global-styles/{id}
Content-Type: application/json

{
    "name": "Nombre Actualizado",
    "properties": {...}
}
```

### Eliminar Estilo

```http
DELETE /wp-json/flavor-vbp/v1/global-styles/{id}
```

### Exportar CSS

```http
GET /wp-json/flavor-vbp/v1/global-styles/export-css
```

## CSS Generado

Los Global Styles se convierten en CSS real:

```css
/* Prefijo: vbp-gs- */
.vbp-gs-heading-1 {
    font-size: 48px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--color-text);
    margin-bottom: 24px;
}

.vbp-gs-button-primary {
    background-color: #3b82f6;
    color: #ffffff;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
}
```

## Panel de Global Styles

### Abrir el Panel

- Paleta de comandos: "Abrir Global Styles"
- Menu: View > Global Styles
- Inspector: Click en icono de Global Styles

### Funcionalidades del Panel

- Ver todos los estilos por categoria
- Buscar estilos
- Crear, editar, eliminar estilos
- Ver cuantos elementos usan cada estilo
- Previsualizar estilos

## Integracion con Inspector

Cuando un elemento tiene un Global Style aplicado:

1. El Inspector muestra el nombre del estilo
2. Un icono indica que es un estilo global
3. Las propiedades del estilo se muestran (solo lectura)
4. Opcion para editar el estilo o desvincular

## Responsive

Los Global Styles pueden tener valores diferentes por breakpoint:

```json
{
    "id": "heading-responsive",
    "properties": {
        "fontSize": "48px"
    },
    "responsive": {
        "tablet": {
            "fontSize": "36px"
        },
        "mobile": {
            "fontSize": "28px"
        }
    }
}
```

## Eventos

```javascript
// Estilo creado
document.addEventListener('vbp:global-style:created', (e) => {
    console.log('Nuevo estilo:', e.detail.style);
});

// Estilo actualizado
document.addEventListener('vbp:global-style:updated', (e) => {
    console.log('Actualizado:', e.detail.style);
});

// Estilo aplicado a elemento
document.addEventListener('vbp:global-style:applied', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Estilo:', e.detail.styleId);
});

// CSS regenerado
document.addEventListener('vbp:global-styles:css-refreshed', () => {
    console.log('CSS actualizado');
});
```

## Consideraciones

- Los estilos globales se guardan a nivel de sitio
- El CSS se regenera automaticamente al guardar cambios
- Los elementos mantienen referencia al ID del estilo
- Si eliminas un estilo, los elementos mantienen los valores pero se desvinculan
- Los estilos soportan variables CSS (Design Tokens)

## Buenas Practicas

1. **Nombra descriptivamente**: `button-primary-large` vs `btn1`
2. **Usa categorias**: Organiza por tipo de componente
3. **Prefiere tokens**: Usa `var(--color-primary)` en vez de `#3b82f6`
4. **Documenta**: Agrega descripcion a los estilos complejos
5. **No abuses**: No crees un estilo para cada elemento

## Solucionar Problemas

### Los cambios no se propagan

1. Verifica que el estilo esta guardado
2. Comprueba que `refreshCSS()` se ejecuto
3. Limpia cache del navegador

### El CSS no se carga

1. Verifica que el stylesheet de global-styles esta encolado
2. Comprueba la consola por errores
3. Regenera el CSS manualmente

### Conflictos de estilos

1. Verifica la especificidad CSS
2. Usa `!important` solo cuando sea necesario
3. Revisa el orden de carga de stylesheets
