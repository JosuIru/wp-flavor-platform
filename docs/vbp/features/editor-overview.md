# Editor Visual - Vista General

Descripcion completa del editor Visual Builder Pro, sus componentes principales y flujo de trabajo.

## Descripcion

El Visual Builder Pro (VBP) es un editor visual fullscreen tipo Figma/Photoshop para WordPress. Permite crear paginas con una experiencia de diseno moderna, incluyendo drag and drop, edicion inline, y herramientas profesionales de diseno.

## Arquitectura del Editor

```
+---------------------------------------------------------------------+
|                        TOOLBAR SUPERIOR                              |
|  [Logo] [Undo][Redo] | [Desktop][Tablet][Mobile] | [Zoom] [Save]    |
+----------+---------------------------------------------+-------------+
|  PANEL   |                 C A N V A S                 | INSPECTOR   |
|  BLOQUES |           (Drag & Drop con Sortable)        |  ESTILOS    |
|          |                                             |             |
|  CAPAS   |  [Elementos editables inline]               |   LAYOUT    |
+----------+---------------------------------------------+-------------+
|                        STATUS BAR                                    |
+---------------------------------------------------------------------+
```

## Componentes Principales

### 1. Toolbar Superior

| Componente | Descripcion |
|------------|-------------|
| Logo/Menu | Acceso a opciones globales, guardar, exportar |
| Undo/Redo | Historial de cambios con Ctrl+Z / Ctrl+Shift+Z |
| Breakpoints | Cambiar entre Desktop, Tablet, Mobile |
| Zoom | Controles de zoom (10% - 400%) |
| Guardar | Estado de guardado y boton guardar |
| Usuarios | Avatares de colaboradores (si hay) |

### 2. Panel de Bloques (Izquierda)

Contiene todos los bloques disponibles organizados por categorias:

- **Layout**: Section, Container, Columns, Grid, Spacer
- **Contenido**: Heading, Text, Image, Button, Icon
- **Media**: Video, Audio, Gallery, Slider
- **Modulos**: Integraciones con modulos de Flavor Platform
- **Avanzado**: HTML, Shortcode, Iframe, Code

### 3. Canvas Central

El area de trabajo principal donde:

- Arrastras bloques desde el panel
- Editas contenido inline (doble clic)
- Redimensionas elementos
- Reorganizas con drag and drop

### 4. Inspector (Derecha)

Panel contextual con las propiedades del elemento seleccionado:

- **Contenido**: Campos especificos del bloque
- **Estilos**: Colores, tipografia, espaciado
- **Layout**: Display, flex, grid
- **Efectos**: Sombras, bordes, filtros
- **Avanzado**: Clases CSS, ID, CSS personalizado

### 5. Panel de Capas

Vista de arbol de todos los elementos:

- Jerarquia visual del documento
- Drag and drop para reorganizar
- Visibilidad y bloqueo de elementos
- Busqueda rapida

## Acceder al Editor

### Desde el Admin de WordPress

1. Ve a **Paginas** > **Todas las paginas**
2. Pasa el cursor sobre una pagina
3. Haz clic en **Editar con VBP**

### Desde la Barra de Admin

En cualquier pagina del frontend:
1. Haz clic en la barra de administracion superior
2. Selecciona **Editar con Visual Builder**

### URL Directa

```
http://TU_SITIO/wp-admin/admin.php?page=vbp-editor&post_id=123
```

## API JavaScript

### Acceder al Store Principal

```javascript
const store = Alpine.store('vbp');

// Propiedades del documento
console.log(store.postId);          // ID del post
console.log(store.elements);        // Elementos del documento
console.log(store.isDirty);         // Cambios sin guardar

// Estado de UI
console.log(store.zoom);            // Nivel de zoom
console.log(store.devicePreview);   // 'desktop', 'tablet', 'mobile'
console.log(store.panels);          // Visibilidad de paneles
```

### Cargar y Guardar Documento

```javascript
// Cargar documento
await store.loadDocument(postId);

// Guardar documento
await store.saveDocument();

// Marcar como modificado
store.setDirty(true);
```

### Eventos Globales

```javascript
// Documento cargado
document.addEventListener('vbp:document:loaded', (e) => {
    console.log('Documento cargado:', e.detail.postId);
});

// Documento guardado
document.addEventListener('vbp:document:saved', (e) => {
    console.log('Guardado a las:', e.detail.savedAt);
});

// Cambios sin guardar
document.addEventListener('vbp:document:dirty', (e) => {
    console.log('Tiene cambios:', e.detail.isDirty);
});
```

## Atajos de Teclado Esenciales

| Atajo | Accion |
|-------|--------|
| `Ctrl+S` | Guardar |
| `Ctrl+Z` | Deshacer |
| `Ctrl+Shift+Z` | Rehacer |
| `Ctrl+K` | Paleta de comandos |
| `Ctrl+B` | Toggle panel bloques |
| `Ctrl+I` | Toggle inspector |
| `Ctrl+L` | Toggle capas |
| `Ctrl+\` | Toggle todos los paneles |
| `?` | Ayuda |

## Configuracion

### PHP Config

```php
// wp-config.php o plugin

// Habilitar modo debug VBP
define('VBP_DEBUG', true);

// Intervalo de autoguardado (segundos)
define('VBP_AUTOSAVE_INTERVAL', 60);

// Maximo de revisiones
define('VBP_MAX_REVISIONS', 50);
```

### JavaScript Config

```javascript
// Configuracion por defecto
const vbpConfig = {
    autosaveInterval: 60000,      // 60 segundos
    undoStackLimit: 100,          // Maximo de undos
    zoomStep: 10,                 // Incremento de zoom
    gridSize: 8,                  // Grid base en px
    snapToGrid: true,             // Activar snap
    showSmartGuides: true         // Guias inteligentes
};
```

## Requisitos del Sistema

- WordPress 6.0+
- PHP 7.4+
- JavaScript ES6
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

## Solucionar Problemas

### El editor no carga

1. Verifica que JavaScript no tiene errores (F12 > Console)
2. Limpia la cache del navegador
3. Desactiva otros plugins que puedan interferir

### Los cambios no se guardan

1. Verifica permisos de escritura del usuario
2. Revisa la conexion a internet
3. Comprueba que la API REST funciona

### El canvas se ve corrupto

1. Refresca la pagina (F5)
2. Limpia la cache del navegador
3. Prueba en modo incognito

## Proximos Pasos

- [Bloques](blocks.md) - Sistema de bloques disponibles
- [Inspector](inspector.md) - Panel de propiedades
- [Capas](layers.md) - Gestion de jerarquia
- [Undo/Redo](undo-redo.md) - Sistema de historial
