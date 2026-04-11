# Variables y Logica

Sistema de variables dinamicas, expresiones condicionales y logica de comportamiento para crear disenos interactivos y adaptativos.

## Descripcion

El sistema de Variables y Logica permite crear disenos que responden a datos dinamicos, condiciones y estados. Similar a las variables de Figma pero con capacidades de logica adicionales, incluyendo expresiones, condicionales y bindings reactivos.

## Como Acceder

- Panel Variables: `Ctrl+Alt+V`
- Inspector > Avanzado > Variables
- Paleta de comandos: "Variables"

## Tipos de Variables

### 1. Variables de Texto

```javascript
{
    name: 'nombreUsuario',
    type: 'text',
    defaultValue: 'Invitado',
    description: 'Nombre del usuario actual'
}
```

### 2. Variables de Numero

```javascript
{
    name: 'contador',
    type: 'number',
    defaultValue: 0,
    min: 0,
    max: 100,
    step: 1
}
```

### 3. Variables de Color

```javascript
{
    name: 'colorPrimario',
    type: 'color',
    defaultValue: '#3b82f6',
    collection: 'brand'
}
```

### 4. Variables Booleanas

```javascript
{
    name: 'estaActivo',
    type: 'boolean',
    defaultValue: true
}
```

### 5. Variables de Modo

```javascript
{
    name: 'tema',
    type: 'mode',
    modes: ['light', 'dark', 'system'],
    defaultMode: 'light'
}
```

## Colecciones de Variables

Agrupa variables relacionadas:

```javascript
const collection = {
    name: 'brand',
    description: 'Colores de marca',
    variables: {
        primary: { type: 'color', value: '#3b82f6' },
        secondary: { type: 'color', value: '#10b981' },
        accent: { type: 'color', value: '#f59e0b' }
    },
    modes: {
        light: { primary: '#3b82f6', secondary: '#10b981' },
        dark: { primary: '#60a5fa', secondary: '#34d399' }
    }
};
```

## Uso en Propiedades

### Binding Simple

Vincula una propiedad a una variable:

```javascript
// En el inspector o programaticamente
{
    backgroundColor: '$brand.primary',
    color: '$text.primary',
    padding: '$spacing.md'
}
```

### Expresiones

Usa expresiones para calcular valores:

```javascript
{
    // Expresion matematica
    fontSize: '${baseSize * 1.5}px',

    // Concatenacion
    content: 'Hola, ${nombreUsuario}!',

    // Condicional simple
    display: '${estaActivo ? "block" : "none"}'
}
```

### Condicionales

Muestra/oculta elementos basado en condiciones:

```javascript
{
    type: 'conditional',
    condition: '$usuario.premium === true',
    then: { display: 'block' },
    else: { display: 'none' }
}
```

## Panel de Variables

### Interfaz

```
VARIABLES
├── Colecciones
│   ├── brand (3 variables)
│   ├── spacing (5 variables)
│   └── typography (4 variables)
├── Variables Locales
│   ├── contador: 5
│   ├── estaActivo: true
│   └── usuario: { name: "Juan" }
└── Modos
    ├── light (activo)
    └── dark
```

### Crear Variable

1. Click en "+ Nueva Variable"
2. Selecciona tipo
3. Asigna nombre y valor
4. Opcionalmente agrega a coleccion

### Editar Variable

1. Click en la variable
2. Modifica valor o propiedades
3. Los cambios se propagan a todos los usos

### Cambiar Modo

1. Selecciona coleccion con modos
2. Click en el modo deseado
3. Todas las variables de la coleccion cambian

## API JavaScript

### VBPVariables

```javascript
const vars = window.VBPVariables;

// Crear variable
vars.create({
    name: 'miVariable',
    type: 'text',
    value: 'valor inicial'
});

// Obtener valor
const valor = vars.get('miVariable');

// Establecer valor
vars.set('miVariable', 'nuevo valor');

// Observar cambios
vars.watch('miVariable', (newValue, oldValue) => {
    console.log('Cambio:', oldValue, '->', newValue);
});

// Dejar de observar
vars.unwatch('miVariable', callback);

// Eliminar variable
vars.delete('miVariable');

// Listar variables
const all = vars.list();
const porTipo = vars.listByType('color');
```

### Colecciones

```javascript
// Crear coleccion
vars.createCollection({
    name: 'brand',
    variables: {
        primary: { type: 'color', value: '#3b82f6' },
        secondary: { type: 'color', value: '#10b981' }
    }
});

// Obtener de coleccion
const color = vars.get('brand.primary');

// Cambiar modo
vars.setMode('brand', 'dark');

// Obtener modo actual
const mode = vars.getMode('brand');
```

### Expresiones

```javascript
// Evaluar expresion
const result = vars.evaluate('${precio * cantidad}');

// Registrar funcion personalizada
vars.registerFunction('formatCurrency', (value) => {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
    }).format(value);
});

// Usar en expresion
const formatted = vars.evaluate('${formatCurrency(total)}');
```

### VBPLogic

```javascript
const logic = window.VBPLogic;

// Crear condicion
logic.createCondition(elementId, {
    condition: '$usuario.rol === "admin"',
    actions: {
        true: { addClass: 'admin-view' },
        false: { addClass: 'user-view' }
    }
});

// Estado condicional
logic.createState(elementId, {
    states: {
        default: { backgroundColor: '$brand.primary' },
        hover: { backgroundColor: '$brand.secondary' },
        active: { backgroundColor: '$brand.accent' }
    },
    transitions: {
        duration: 0.3,
        easing: 'ease-out'
    }
});

// Bindings reactivos
logic.bind(elementId, 'textContent', '$contador');
logic.bind(elementId, 'style.color', '$theme.text');

// Unbind
logic.unbind(elementId, 'textContent');
```

## Store de Alpine

```javascript
const store = Alpine.store('vbpLogic');

// Variables
store.variables;           // Todas las variables
store.collections;         // Colecciones
store.currentModes;        // Modos activos por coleccion

// Metodos
store.get(varPath);
store.set(varPath, value);
store.evaluate(expression);
store.setMode(collection, mode);
```

## Eventos

```javascript
// Variable cambiada
document.addEventListener('vbp:variable:changed', (e) => {
    console.log('Variable:', e.detail.name);
    console.log('Valor:', e.detail.value);
    console.log('Anterior:', e.detail.previousValue);
});

// Modo cambiado
document.addEventListener('vbp:mode:changed', (e) => {
    console.log('Coleccion:', e.detail.collection);
    console.log('Nuevo modo:', e.detail.mode);
});

// Condicion evaluada
document.addEventListener('vbp:condition:evaluated', (e) => {
    console.log('Elemento:', e.detail.elementId);
    console.log('Resultado:', e.detail.result);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Alt+V` | Abrir panel de variables |
| `Ctrl+Shift+M` | Cambiar modo |

## Integracion con Inspector

En el inspector, las propiedades muestran un icono de variable cuando soportan binding:

1. Click en el icono de variable
2. Selecciona variable existente o crea nueva
3. La propiedad se actualiza automaticamente

### Indicadores Visuales

- **Icono azul**: Propiedad vinculada a variable
- **Icono naranja**: Propiedad con expresion
- **Icono verde**: Propiedad condicional

## Funciones Incorporadas

| Funcion | Descripcion | Ejemplo |
|---------|-------------|---------|
| `upper(str)` | Mayusculas | `${upper(nombre)}` |
| `lower(str)` | Minusculas | `${lower(email)}` |
| `capitalize(str)` | Primera mayuscula | `${capitalize(titulo)}` |
| `truncate(str, n)` | Cortar texto | `${truncate(descripcion, 100)}` |
| `round(num)` | Redondear | `${round(precio)}` |
| `floor(num)` | Redondear abajo | `${floor(cantidad)}` |
| `ceil(num)` | Redondear arriba | `${ceil(total)}` |
| `formatNumber(num)` | Formato local | `${formatNumber(1234.56)}` |
| `formatDate(date, fmt)` | Formato fecha | `${formatDate(hoy, 'DD/MM/YYYY')}` |
| `if(cond, then, else)` | Condicional | `${if(activo, 'Si', 'No')}` |

## Persistencia

### Local Storage

```javascript
// Las variables pueden persistir en localStorage
vars.create({
    name: 'preferencias',
    type: 'object',
    value: { tema: 'dark' },
    persist: true  // Guardar en localStorage
});
```

### URL Parameters

```javascript
// Leer de URL
vars.bindToURL('filtro', 'filter');  // ?filter=valor

// La variable 'filtro' se actualiza automaticamente
```

### WordPress Options

```javascript
// Vincular a option de WordPress
vars.bindToOption('siteName', 'blogname');
```

## Consideraciones

### Performance

- Las expresiones se evaluan en cada cambio
- Usa variables simples cuando sea posible
- Evita expresiones muy complejas
- Los watchers se ejecutan sincronamente

### Debugging

```javascript
// Activar modo debug
vars.setDebug(true);

// Ver todas las variables
console.log(vars.dump());

// Rastrear cambios
vars.trace('miVariable');  // Log cada cambio
```

## Solucionar Problemas

### La variable no se actualiza

1. Verifica el nombre de la variable
2. Comprueba que el binding es correcto
3. Revisa que no hay errores en la expresion

### El modo no cambia

1. Verifica que la coleccion tiene modos definidos
2. Comprueba que el modo existe
3. Revisa que las variables del modo estan definidas

### Expresion da error

1. Verifica la sintaxis de la expresion
2. Comprueba que las variables existen
3. Revisa tipos de datos (ej: suma de string y numero)
