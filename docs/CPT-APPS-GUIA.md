# Guía: Mostrar Custom Post Types en Apps Móviles

Esta guía te enseña cómo configurar qué Custom Post Types (CPT) de tu WordPress se mostrarán como secciones navegables en tus apps móviles.

---

## 🎯 ¿Qué es esta funcionalidad?

Permite que cualquier tipo de contenido personalizado de tu WordPress (productos, eventos, cursos, portafolio, etc.) aparezca automáticamente en tu app móvil como una sección con su propio icono, color y configuración.

**Ejemplo:**
- Tienes un CPT "Productos" → Aparece en la app con icono de bolsa y color azul
- Tienes un CPT "Eventos" → Aparece en la app con icono de calendario y color naranja
- Tienes un CPT "Cursos" → Aparece en la app con icono de escuela y color verde

---

## ⚙️ Configuración Paso a Paso

### 1. Acceder a la Configuración

```
WordPress Admin > Flavor Platform > Contenido Apps
```

### 2. Ver Custom Post Types Disponibles

Verás tarjetas con todos los CPTs públicos de tu WordPress:
- `post` (Entradas)
- `page` (Páginas)
- Cualquier CPT creado por ti o plugins (productos, eventos, etc.)

**Cada tarjeta muestra:**
- Nombre del CPT
- Slug del CPT (`product`, `evento`, etc.)
- Número de publicaciones
- Taxonomías asociadas (categorías, etiquetas, etc.)

### 3. Activar un CPT para la App

1. **Hacer click en el toggle** (interruptor) de la tarjeta
2. La tarjeta se expandirá mostrando las opciones de configuración

### 4. Configurar el CPT

#### Nombre en la App
```
Campo: "Nombre en la App"
Ejemplo: "Nuestros Productos"
```
Este es el nombre que verán los usuarios en la app.

#### Descripción
```
Campo: "Descripción"
Ejemplo: "Explora nuestro catálogo completo de productos"
```
Texto descriptivo que aparece en la app.

#### Icono
Selecciona un icono de Material Design:
- `shopping_bag` → Bolsa de compras
- `event` → Calendario
- `school` → Escuela
- `article` → Artículo
- `description` → Documento
- `work` → Trabajo/Portafolio
- `people` → Personas/Equipo
- Y más...

#### Color
Selecciona el color que representará esta sección:
- Azul (`#2196F3`)
- Verde (`#4CAF50`)
- Naranja (`#FF9800`)
- Púrpura (`#9C27B0`)
- Rojo (`#F44336`)
- Y más...

#### Orden
```
Campo: "Orden"
Valor: 10, 20, 30...
```
Número que determina el orden de aparición. Menor = aparece primero.

**Ejemplo:**
- Productos: 10 (aparece primero)
- Eventos: 20 (aparece segundo)
- Blog: 30 (aparece tercero)

#### Opciones de Visualización

Marca las opciones que quieres mostrar en la app:

- ☑ **Mostrar en navegación**: Aparece en el menú principal
- ☑ **Imagen destacada**: Muestra la imagen destacada de cada post
- ☐ **Mostrar autor**: Muestra quién escribió el post
- ☑ **Mostrar fecha**: Muestra cuándo se publicó
- ☑ **Mostrar extracto**: Muestra un resumen del contenido
- ☑ **Mostrar categorías**: Muestra las categorías del post
- ☐ **Mostrar tags**: Muestra las etiquetas
- ☑ **Permitir búsqueda**: Los usuarios pueden buscar en este contenido
- ☑ **Permitir filtros**: Los usuarios pueden filtrar por categoría, fecha, etc.

### 5. Guardar Cambios

Click en el botón flotante **"Guardar Cambios"** (esquina inferior derecha).

---

## 📱 Cómo se Ve en la App

### En el Menú Principal
```
┌─────────────────────┐
│  ☰ Mi App          │
├─────────────────────┤
│  🛍️  Productos      │ ← Tu CPT configurado
│  📅  Eventos        │
│  📰  Blog           │
│  👤  Perfil         │
└─────────────────────┘
```

### Al Hacer Click
```
┌─────────────────────┐
│  ← Productos        │
├─────────────────────┤
│  🔍 Buscar...       │ ← Si "Permitir búsqueda" activo
├─────────────────────┤
│  [Filtros▾]         │ ← Si "Permitir filtros" activo
├─────────────────────┤
│  ┌───────┐          │
│  │ FOTO  │ Producto 1│ ← Con imagen destacada
│  └───────┘ $29.99   │
│  Descripción corta...│ ← Extracto
│  📁 Categoría       │ ← Si "Mostrar categorías" activo
│  📅 Hace 2 días     │ ← Si "Mostrar fecha" activo
├─────────────────────┤
│  ┌───────┐          │
│  │ FOTO  │ Producto 2│
│  └───────┘ $39.99   │
│  ...                │
└─────────────────────┘
```

### Vista Individual
```
┌─────────────────────┐
│  ← Producto 1       │
├─────────────────────┤
│  ┌───────────────┐  │
│  │   IMAGEN      │  │
│  │   DESTACADA   │  │
│  └───────────────┘  │
│                     │
│  Producto 1         │
│  $29.99             │
│                     │
│  📁 Categoría       │
│  📅 Publicado...    │
│  ✍️  Autor         │ ← Si "Mostrar autor" activo
│                     │
│  Contenido completo │
│  del producto con   │
│  toda la descripción│
│  formateada...      │
│                     │
│  [Añadir al carrito]│
└─────────────────────┘
```

---

## 🔌 Endpoints de la API

Una vez configurado, tu app puede acceder a estos endpoints:

### Obtener Lista de CPTs Configurados
```
GET /wp-json/app-discovery/v1/custom-post-types
```

**Respuesta:**
```json
{
  "success": true,
  "cpts": [
    {
      "id": "product",
      "name": "Productos",
      "description": "Nuestro catálogo completo",
      "icon": "shopping_bag",
      "color": "#2196F3",
      "order": 10,
      "show_in_navigation": true,
      "endpoint": "https://tusitio.com/wp-json/app-discovery/v1/cpt/product",
      "total_posts": 45
    }
  ],
  "total": 1
}
```

### Obtener Posts de un CPT
```
GET /wp-json/app-discovery/v1/cpt/product?page=1&per_page=10
```

**Parámetros opcionales:**
- `page`: Número de página (default: 1)
- `per_page`: Posts por página (default: 10)
- `category`: Filtrar por categoría
- `tag`: Filtrar por etiqueta
- `search`: Buscar por texto

**Respuesta:**
```json
{
  "success": true,
  "posts": [
    {
      "id": 123,
      "title": "Producto 1",
      "slug": "producto-1",
      "link": "https://tusitio.com/product/producto-1",
      "date": "2025-02-04",
      "date_formatted": "04 Feb 2025",
      "featured_image": {
        "url": "https://tusitio.com/wp-content/uploads/2025/02/producto.jpg",
        "thumbnail": "...",
        "medium": "...",
        "alt": "Producto 1"
      },
      "excerpt": "Descripción corta del producto...",
      "categories": [
        {"id": 5, "name": "Electrónica", "slug": "electronica"}
      ]
    }
  ],
  "pagination": {
    "total": 45,
    "total_pages": 5,
    "current_page": 1,
    "per_page": 10
  }
}
```

### Obtener Post Individual
```
GET /wp-json/app-discovery/v1/cpt/product/123
```

**Respuesta:**
```json
{
  "success": true,
  "post": {
    "id": 123,
    "title": "Producto 1",
    "content": "<p>Contenido completo HTML...</p>",
    "content_raw": "Contenido sin formato...",
    "featured_image": {...},
    "categories": [...],
    "tags": [...],
    "custom_fields": {
      "precio": "29.99",
      "stock": "15"
    }
  }
}
```

### Información en System Info
Los CPTs también aparecen en el endpoint principal:
```
GET /wp-json/app-discovery/v1/info
```

En la respuesta verás:
```json
{
  "custom_post_types": {
    "available": true,
    "endpoint": "https://tusitio.com/wp-json/app-discovery/v1/custom-post-types",
    "cpts": [
      {
        "id": "product",
        "name": "Productos",
        "icon": "shopping_bag",
        "color": "#2196F3",
        ...
      }
    ],
    "total": 1
  }
}
```

---

## 💡 Casos de Uso Comunes

### 1. E-commerce (Productos)
```
CPT: product
Icono: shopping_bag
Color: #2196F3 (Azul)
Mostrar: Imagen, Extracto, Categorías
Permitir: Búsqueda, Filtros
```

### 2. Blog/Noticias (Entradas)
```
CPT: post
Icono: article
Color: #4CAF50 (Verde)
Mostrar: Imagen, Fecha, Autor, Categorías, Tags
Permitir: Búsqueda, Filtros
```

### 3. Eventos
```
CPT: evento
Icono: event
Color: #FF9800 (Naranja)
Mostrar: Imagen, Fecha, Extracto
Permitir: Búsqueda, Filtros por categoría
```

### 4. Cursos
```
CPT: course
Icono: school
Color: #9C27B0 (Púrpura)
Mostrar: Imagen, Extracto, Categorías
Ocultar: Autor, Tags
```

### 5. Portafolio
```
CPT: portfolio
Icono: work
Color: #607D8B (Gris azulado)
Mostrar: Imagen (destacar)
Ocultar: Autor, Fecha, Tags
```

### 6. Equipo/Staff
```
CPT: team
Icono: people
Color: #E91E63 (Rosa)
Mostrar: Imagen
Ocultar: Fecha, Categorías, Tags
```

---

## 🎨 Personalización Avanzada

### Custom Fields

Si quieres que la app acceda a campos personalizados (creados con ACF, Pods, etc.):

1. Ir a **Flavor Platform > Configuración de Apps**
2. En "Custom Fields para CPT", añadir los nombres de los campos
3. La app recibirá estos campos en `custom_fields`

**Ejemplo:**
```php
// En la configuración
flavor_app_cpt_custom_fields_product = ['precio', 'stock', 'marca']

// En la app recibirás:
{
  "custom_fields": {
    "precio": "29.99",
    "stock": "15",
    "marca": "Samsung"
  }
}
```

### Filtros Personalizados

Puedes modificar cómo se formatean los posts con el filtro:

```php
add_filter('flavor_app_cpt_format_post', function($data, $post, $config, $full) {
    // Añadir datos personalizados
    if ($post->post_type === 'product') {
        $data['price'] = get_post_meta($post->ID, 'price', true);
        $data['on_sale'] = get_post_meta($post->ID, 'on_sale', true);
    }

    return $data;
}, 10, 4);
```

---

## 🧪 Testing

### 1. Verifica que el endpoint funciona

Abre en el navegador:
```
https://tusitio.com/wp-json/app-discovery/v1/custom-post-types
```

Deberías ver JSON con tus CPTs configurados.

### 2. Verifica posts de un CPT

```
https://tusitio.com/wp-json/app-discovery/v1/cpt/product
```

Deberías ver lista de productos (o el CPT que configuraste).

### 3. Verifica post individual

```
https://tusitio.com/wp-json/app-discovery/v1/cpt/product/123
```

Reemplaza `123` con un ID real de post.

---

## ❓ Preguntas Frecuentes

### ¿Puedo mostrar páginas normales de WordPress?

Sí, `page` es un CPT y aparecerá en la lista. Configúralo como cualquier otro.

### ¿Funciona con WooCommerce?

Sí, `product` de WooCommerce es un CPT y puedes configurarlo.

### ¿Puedo crear mis propios iconos?

Los iconos vienen de Material Icons. Puedes ver todos los disponibles en:
https://fonts.google.com/icons

### ¿Cómo añado más opciones de color?

Por ahora están predefinidos. En futuras versiones podrás usar un color picker.

### ¿Se actualiza automáticamente en la app?

Sí, cada vez que la app se inicia consulta el endpoint y obtiene la configuración actualizada.

### ¿Puedo ocultar un CPT del menú pero mantener el endpoint?

Sí, desactiva "Mostrar en navegación" pero mantén el CPT habilitado. La app no lo mostrará en el menú pero el endpoint seguirá funcionando.

---

## 🔧 Troubleshooting

### No veo mis CPTs en la lista

**Solución:**
- Verifica que el CPT sea público (`public => true` en su registro)
- Verifica que no esté en la lista de excluidos

### El endpoint retorna 403

**Solución:**
- Verifica que el CPT esté habilitado en la configuración
- Verifica que existan posts publicados

### No aparece la imagen destacada

**Solución:**
- Verifica que la opción "Imagen destacada" esté activa
- Verifica que los posts tengan imagen destacada asignada
- Verifica que el CPT soporte `thumbnail`

---

## ✅ Checklist de Configuración

- [ ] Acceder a `Flavor Platform > Contenido Apps`
- [ ] Activar los CPTs que quieres mostrar
- [ ] Configurar nombre, descripción, icono y color
- [ ] Establecer el orden de aparición
- [ ] Configurar opciones de visualización
- [ ] Guardar cambios
- [ ] Probar endpoint en navegador
- [ ] Verificar que la app muestre los CPTs
- [ ] Probar navegación en la app
- [ ] Verificar que se muestren imágenes y datos correctos

---

¡Listo! Ahora tus Custom Post Types aparecerán automáticamente en tu app móvil. 🚀
