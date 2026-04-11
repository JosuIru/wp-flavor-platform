# Visual Builder Pro - Guia de Inicio

## Requisitos Previos

Antes de comenzar, asegurate de tener:

- WordPress 6.0 o superior
- Flavor Platform instalado y activado
- Tema `flavor-starter` activo (opcional pero recomendado)
- Navegador moderno (Chrome, Firefox, Safari, Edge)

## Activar Visual Builder Pro

VBP se activa automaticamente con Flavor Platform. Para verificar:

```bash
# Verificar que el plugin esta activo
wp plugin is-active flavor-platform

# Verificar que VBP responde
curl -s "http://TU_SITIO/wp-json/flavor-vbp/v1/claude/status"
```

## Abrir el Editor

Hay varias formas de acceder al editor VBP:

### 1. Desde el Admin de WordPress

1. Ve a **Paginas** > **Todas las paginas**
2. Pasa el cursor sobre una pagina
3. Haz clic en **Editar con VBP**

### 2. Desde la Barra de Admin

En cualquier pagina del frontend:
1. Haz clic en la barra de administracion superior
2. Selecciona **Editar con Visual Builder**

### 3. URL Directa

```
http://TU_SITIO/wp-admin/admin.php?page=vbp-editor&post_id=123
```

## Interfaz del Editor

### Panel Izquierdo - Bloques

Contiene todos los bloques disponibles organizados por categorias:

- **Layout**: Section, Container, Columns, Grid
- **Contenido**: Heading, Text, Image, Button
- **Modulos**: Integraciones con modulos de Flavor Platform
- **Avanzado**: HTML, Shortcode, Iframe

### Canvas Central

El area de trabajo donde arrastras y editas elementos:

- **Arrastrar y soltar** bloques desde el panel izquierdo
- **Seleccionar** elementos haciendo clic
- **Editar inline** haciendo doble clic en textos
- **Redimensionar** arrastrando los handles

### Panel Derecho - Inspector

Muestra las propiedades del elemento seleccionado:

- **Contenido**: Campos especificos del bloque
- **Estilos**: Colores, tipografia, espaciado
- **Layout**: Display, flex, grid
- **Avanzado**: Clases CSS, ID, CSS personalizado

### Toolbar Superior

- **Logo**: Menu y opciones globales
- **Undo/Redo**: Deshacer y rehacer cambios
- **Dispositivos**: Cambiar entre Desktop, Tablet, Mobile
- **Zoom**: Controles de zoom del canvas
- **Guardar**: Guardar cambios

## Flujo de Trabajo Basico

### 1. Crear una Seccion

```
1. Arrastra un bloque "Section" al canvas
2. Configura el fondo en el Inspector
3. Ajusta el padding y margin
```

### 2. Agregar Contenido

```
1. Arrastra un "Heading" dentro de la seccion
2. Haz doble clic para editar el texto
3. Ajusta estilos en el Inspector
```

### 3. Guardar Cambios

```
1. Presiona Ctrl+S (o Cmd+S en Mac)
2. O haz clic en el boton Guardar
```

## Atajos de Teclado Esenciales

| Atajo | Accion |
|-------|--------|
| `Ctrl+S` | Guardar |
| `Ctrl+Z` | Deshacer |
| `Ctrl+Shift+Z` | Rehacer |
| `Ctrl+C` | Copiar |
| `Ctrl+V` | Pegar |
| `Ctrl+D` | Duplicar |
| `Delete` | Eliminar |
| `Escape` | Deseleccionar |
| `Ctrl+K` | Paleta de comandos |
| `?` | Ayuda |

## Proximos Pasos

Una vez familiarizado con lo basico, explora:

1. **[Simbolos](features/symbols.md)** - Crear componentes reutilizables
2. **[Estilos Globales](features/global-styles.md)** - Definir estilos compartidos
3. **[Responsive](features/responsive-variants.md)** - Disenar para multiples dispositivos
4. **[Animaciones](features/animation-builder.md)** - Crear animaciones CSS

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

## Recursos Adicionales

- [API REST](api/rest-endpoints.md)
- [Alpine Stores](api/alpine-stores.md)
- [Atajos de Teclado](keyboard-shortcuts.md)
