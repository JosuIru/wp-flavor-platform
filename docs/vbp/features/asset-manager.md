# Asset Manager (Gestor de Assets)

Panel centralizado para gestionar medios: imagenes, SVGs, videos, iconos e integracion con Unsplash.

## Descripcion

El Asset Manager proporciona un unico lugar para explorar, buscar y gestionar todos los recursos multimedia del sitio. Incluye favoritos, colecciones, subida por drag & drop e integracion con la biblioteca de medios de WordPress.

## Abrir el Asset Manager

- Desde el Inspector al seleccionar un campo de imagen
- Menu: View > Asset Manager
- Paleta de comandos: "Abrir Asset Manager"

## Interfaz

### Tabs

| Tab | Contenido |
|-----|-----------|
| **Imagenes** | Biblioteca de medios de WordPress (jpg, png, gif, webp) |
| **SVGs** | Vectoriales SVG |
| **Videos** | Archivos de video (mp4, webm) |
| **Iconos** | Biblioteca de iconos integrada |
| **Unsplash** | Busqueda en Unsplash |

### Barra Superior

- **Busqueda**: Filtra assets por nombre
- **Filtros**: Por fecha, tipo, tamano
- **Vista**: Grid o lista
- **Favoritos**: Mostrar solo favoritos

### Grid de Assets

- **Thumbnail**: Vista previa del asset
- **Nombre**: Nombre del archivo
- **Info**: Dimensiones, tamano
- **Acciones**: Favorito, eliminar, info

## Como Usar

### Seleccionar un Asset

1. Abre el Asset Manager
2. Navega o busca el asset deseado
3. Haz click para seleccionar
4. Click en "Insertar" o doble-click

### Subir Nuevo Asset

**Opcion 1: Drag & Drop**
1. Arrastra archivos desde tu computadora
2. Sueltalos en el area del Asset Manager
3. Los archivos se suben automaticamente

**Opcion 2: Boton Subir**
1. Click en "Subir"
2. Selecciona archivos desde el explorador
3. Espera a que se complete la subida

### Marcar como Favorito

1. Hover sobre un asset
2. Click en el icono de estrella
3. El asset aparecera en la seccion de favoritos

### Crear Coleccion

1. Selecciona multiples assets
2. Click en "Agregar a coleccion"
3. Elige coleccion existente o crea nueva

## Unsplash Integration

### Configuracion

Unsplash requiere una API key. Para configurar:

1. Ve a Ajustes > VBP > Unsplash
2. Ingresa tu API key de Unsplash
3. Guarda los cambios

### Buscar en Unsplash

1. Abre el tab "Unsplash"
2. Escribe tu busqueda
3. Opcionalmente filtra por orientacion
4. Click en una imagen para seleccionar
5. La imagen se descarga automaticamente a tu biblioteca

### Filtros de Unsplash

- **Orientacion**: Landscape, Portrait, Squarish
- **Color**: Filtrar por color dominante

## Iconos Integrados

### Librerias Disponibles

- **FontAwesome**: Free icons
- **Material Icons**: Google Material Design
- **Heroicons**: Tailwind icons

### Buscar Iconos

1. Abre el tab "Iconos"
2. Selecciona la categoria o busca
3. Click en el icono para insertarlo

### Personalizar Icono

Despues de insertar:
- Color del icono
- Tamano
- Rotacion

## API JavaScript

### Acceder al Sistema

```javascript
const assetManager = window.VBPAssetManager;
```

### Abrir con Opciones

```javascript
assetManager.open({
    tab: 'images',           // Tab inicial
    targetElement: elementId, // Elemento destino
    targetField: 'src',      // Campo a actualizar
    onSelect: function(asset) {
        console.log('Asset seleccionado:', asset);
    }
});
```

### Cerrar

```javascript
assetManager.close();
```

### Cargar Assets

```javascript
// Cargar manualmente
assetManager.loadAssets();

// Obtener assets cargados
const assets = assetManager.assets;
```

### Busqueda

```javascript
// Establecer busqueda
assetManager.searchQuery = 'landscape';
assetManager.loadAssets();
```

### Favoritos

```javascript
// Obtener favoritos
const favorites = assetManager.favorites;

// Toggle favorito
assetManager.toggleFavorite(assetId);

// Mostrar solo favoritos
assetManager.showFavoritesOnly = true;
assetManager.loadAssets();
```

### Colecciones

```javascript
// Obtener colecciones
const collections = assetManager.collections;

// Crear coleccion
assetManager.createCollection('Mi Coleccion');

// Agregar a coleccion
assetManager.addToCollection(assetId, collectionId);

// Filtrar por coleccion
assetManager.activeCollection = 'mi-coleccion';
assetManager.loadAssets();
```

### Unsplash

```javascript
// Buscar en Unsplash
assetManager.unsplashQuery = 'mountains';
assetManager.unsplashOrientation = 'landscape';
assetManager.searchUnsplash();

// Descargar imagen
assetManager.downloadUnsplash(unsplashImageId);
```

### Subir Archivo

```javascript
// Subir programaticamente
assetManager.uploadFile(file).then(function(asset) {
    console.log('Subido:', asset);
});
```

## Eventos

```javascript
// Asset seleccionado
document.addEventListener('vbp:asset:selected', (event) => {
    console.log('Asset:', event.detail.asset);
    console.log('URL:', event.detail.url);
});

// Asset subido
document.addEventListener('vbp:asset:uploaded', (event) => {
    console.log('Nuevo asset:', event.detail.asset);
});

// Favorito cambiado
document.addEventListener('vbp:asset:favorite:changed', (event) => {
    console.log('Asset:', event.detail.assetId);
    console.log('Es favorito:', event.detail.isFavorite);
});
```

## API REST

### Listar Assets

```http
GET /wp-json/flavor-vbp/v1/assets?type=images&page=1&per_page=24
```

### Buscar Assets

```http
GET /wp-json/flavor-vbp/v1/assets?search=landscape&type=images
```

### Subir Asset

```http
POST /wp-json/flavor-vbp/v1/assets
Content-Type: multipart/form-data
```

### Favoritos

```http
POST /wp-json/flavor-vbp/v1/assets/{id}/favorite
DELETE /wp-json/flavor-vbp/v1/assets/{id}/favorite
```

### Colecciones

```http
GET /wp-json/flavor-vbp/v1/assets/collections
POST /wp-json/flavor-vbp/v1/assets/collections
POST /wp-json/flavor-vbp/v1/assets/{id}/collection/{collectionId}
```

## Configuracion

### Items por Pagina

```javascript
assetManager.itemsPerPage = 48; // Por defecto 24
```

### Tipos Soportados

Los tipos de archivo se definen en el backend. Por defecto:

- **Imagenes**: jpg, jpeg, png, gif, webp
- **SVGs**: svg
- **Videos**: mp4, webm, ogg

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Enter` | Insertar asset seleccionado |
| `Escape` | Cerrar Asset Manager |
| `Ctrl+F` | Focus en busqueda |
| `Arrow Keys` | Navegar grid |
| `F` | Toggle favorito |

## Consideraciones

- Los assets se cargan de forma paginada para mejor performance
- Los favoritos se guardan en localStorage del usuario
- Las colecciones se guardan en la base de datos
- Unsplash tiene limites de API (50 requests/hora en plan free)

## Solucionar Problemas

### Los assets no cargan

1. Verifica la conexion a internet
2. Comprueba permisos de usuario
3. Revisa la consola por errores de API

### Unsplash no funciona

1. Verifica que la API key esta configurada
2. Comprueba que no has excedido el limite de requests
3. Revisa que el dominio esta autorizado en tu app Unsplash

### La subida falla

1. Verifica el tamano del archivo (max upload size de PHP)
2. Comprueba los tipos de archivo permitidos
3. Asegurate de tener permisos de escritura
