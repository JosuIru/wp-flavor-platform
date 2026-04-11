# Simbolos (Symbols)

Sistema de componentes reutilizables con instancias sincronizadas. Los simbolos son como "componentes maestros" cuyas instancias se actualizan automaticamente cuando se modifica el original.

## Diferencia con Componentes

| Caracteristica | Componentes | Simbolos |
|----------------|-------------|----------|
| Sincronizacion | No - copias independientes | Si - instancias vinculadas |
| Edicion | Cada copia es unica | Cambios se propagan a todas las instancias |
| Uso ideal | Bloques unicos | Headers, footers, cards repetidas |

## Como Usar

### Crear un Simbolo

**Opcion 1: Desde la Seleccion**

1. Selecciona uno o mas elementos en el canvas
2. Presiona `Ctrl+Shift+Y` o usa la paleta de comandos (`Ctrl+K`)
3. Escribe un nombre para el simbolo
4. Opcionalmente define propiedades expuestas (overrides)

**Opcion 2: Desde el Panel de Simbolos**

1. Abre el panel con `F8` o desde el menu
2. Haz clic en "Crear Simbolo"
3. Selecciona los elementos a incluir

### Insertar una Instancia

1. Abre el panel de simbolos (`F8`)
2. Arrastra el simbolo al canvas
3. O usa `Ctrl+Alt+O` para buscar e insertar rapidamente

### Editar el Master

1. Selecciona cualquier instancia del simbolo
2. Haz clic en "Ir al Master" o presiona `Ctrl+Shift+G`
3. Realiza los cambios
4. Todas las instancias se actualizaran automaticamente

### Desvincular una Instancia

Si necesitas que una instancia sea independiente:

1. Selecciona la instancia
2. Presiona `Ctrl+Alt+U` o usa "Desvincular del Simbolo"
3. La instancia se convierte en elementos normales

## Overrides (Propiedades Expuestas)

Los overrides permiten personalizar ciertas propiedades en cada instancia sin romper la sincronizacion.

### Propiedades Soportadas

- Textos (titulos, parrafos)
- Imagenes (src, alt)
- Enlaces (href)
- Colores (fondo, texto)
- Visibilidad de elementos hijos

### Configurar Overrides

1. Edita el simbolo master
2. Selecciona el elemento que quieres hacer editable
3. En el Inspector, marca "Exponer para override"
4. Las instancias podran modificar esa propiedad

## Variantes

Los simbolos pueden tener variantes predefinidas (ej: boton primario, secundario, ghost).

### Crear Variante

1. Edita el simbolo master
2. Haz clic en "Agregar Variante"
3. Nombra la variante (ej: "secundario")
4. Modifica las propiedades

### Usar Variante

En cualquier instancia:
1. Selecciona la instancia
2. En el Inspector, elige la variante del dropdown

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Shift+Y` | Crear simbolo desde seleccion |
| `Ctrl+Alt+O` | Insertar simbolo |
| `Ctrl+Alt+U` | Desvincular instancia |
| `Ctrl+Shift+G` | Ir al master |
| `F8` | Abrir panel de simbolos |

## API Alpine Store

El sistema de simbolos extiende el store VBP:

```javascript
// Acceder al store
const store = Alpine.store('vbp');

// Propiedades disponibles
store.symbols              // Array de simbolos cargados
store.symbolInstances      // Mapa de element_id -> symbol_id
store.symbolsLoading       // Estado de carga

// Metodos
store.createSymbolFromSelection(nombre, exposedProps)
store.insertSymbolInstance(symbolId, parentId, position)
store.detachInstance(elementId)
store.goToMaster(symbolId)
store.updateSymbolMaster(symbolId, cambios)
store.createVariant(symbolId, variantName, propiedades)
store.applyVariant(instanceId, variantName)
```

## API REST

### Listar Simbolos

```http
GET /wp-json/flavor-vbp/v1/symbols
```

Respuesta:
```json
{
  "success": true,
  "symbols": [
    {
      "id": "sym_abc123",
      "name": "Header Principal",
      "category": "layout",
      "variants": ["default", "transparente"],
      "usageCount": 15
    }
  ]
}
```

### Crear Simbolo

```http
POST /wp-json/flavor-vbp/v1/symbols
Content-Type: application/json

{
  "name": "Mi Simbolo",
  "elements": [...],
  "exposedProps": ["titulo", "imagen"]
}
```

### Actualizar Simbolo

```http
PUT /wp-json/flavor-vbp/v1/symbols/{id}
Content-Type: application/json

{
  "name": "Nombre actualizado",
  "elements": [...]
}
```

### Eliminar Simbolo

```http
DELETE /wp-json/flavor-vbp/v1/symbols/{id}
```

## Buenas Practicas

1. **Nombra descriptivamente**: Usa nombres como "Card Producto" en vez de "Card 1"

2. **Expone solo lo necesario**: No expongas todas las propiedades, solo las que realmente varian

3. **Usa variantes para estados**: En vez de crear multiples simbolos, usa variantes

4. **Organiza por categorias**: Agrupa simbolos relacionados (Headers, Footers, Cards, etc.)

5. **Documenta los overrides**: En el nombre o descripcion, indica que propiedades son editables

## Consideraciones

- Los simbolos se guardan a nivel de sitio, no por pagina
- Eliminar un simbolo master NO elimina las instancias (se convierten en elementos normales)
- Los simbolos soportan anidacion (un simbolo dentro de otro)
- La sincronizacion es automatica pero puedes forzarla con "Sincronizar Simbolos"
