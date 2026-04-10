# 📱 API Móvil - Marketplace

## Descripción

API REST optimizada para aplicaciones móviles del módulo **Marketplace**.

Plataforma comunitaria para publicar anuncios de **regalo, venta, cambio y alquiler** entre usuarios.

## Base URL

```
https://tu-sitio.com/wp-json/flavor-platform/v1/marketplace
```

## Autenticación

Endpoints que requieren autenticación usan **JWT** o **sesión de WordPress**.

Header requerido:
```
Authorization: Bearer {tu-token-jwt}
```

## Endpoints

### 1. Listar Anuncios

Obtiene anuncios públicos.

```http
GET /marketplace/anuncios
```

**Query Parameters**:
- `busqueda` (string, opcional): Búsqueda en título y descripción
- `tipo` (string, opcional): `todos`, `regalo`, `venta`, `cambio`, `alquiler` (default: todos)
- `categoria` (string, opcional): Slug de categoría
- `limite` (int, opcional): Resultados por página (default: 20)
- `pagina` (int, opcional): Número de página (default: 1)

**Respuesta**:
```json
{
  "success": true,
  "anuncios": [
    {
      "id": 42,
      "titulo": "Bicicleta de montaña",
      "descripcion": "Bicicleta en buen estado, poco uso...",
      "tipo": "venta",
      "categoria": {
        "id": 5,
        "slug": "deportes",
        "nombre": "Deportes"
      },
      "precio": 150.00,
      "estado": "bueno",
      "ubicacion": "Madrid",
      "vendido": false,
      "imagen": "https://tu-sitio.com/wp-content/uploads/bici.jpg",
      "fecha_publicacion": "2024-01-15T10:30:00+00:00",
      "autor": {
        "id": 10,
        "nombre": "Juan Pérez"
      }
    }
  ],
  "total": 45,
  "pagina": 1,
  "limite": 20,
  "total_paginas": 3
}
```

**cURL**:
```bash
curl "https://tu-sitio.com/wp-json/flavor-platform/v1/marketplace/anuncios?tipo=venta&categoria=deportes"
```

---

### 2. Crear Anuncio

Publica un nuevo anuncio.

```http
POST /marketplace/anuncios
```

**🔒 Requiere autenticación**

**Body** (JSON):
```json
{
  "titulo": "Bicicleta de montaña",
  "descripcion": "Bicicleta en buen estado, poco uso. Cambios Shimano, frenos de disco.",
  "tipo": "venta",
  "categoria": "deportes",
  "precio": 150.00,
  "estado": "bueno",
  "ubicacion": "Madrid"
}
```

**Campos**:
- `titulo` (string, requerido): Título del anuncio
- `descripcion` (string, requerido): Descripción detallada
- `tipo` (string, requerido): `regalo`, `venta`, `cambio`, `alquiler`
- `categoria` (string, opcional): Slug de categoría
- `precio` (number, opcional): Precio (no aplicable para regalo/cambio)
- `estado` (string, opcional): Estado del artículo (`nuevo`, `como_nuevo`, `bueno`, `aceptable`)
- `ubicacion` (string, opcional): Ubicación del artículo
- `imagenes` (array, opcional): IDs de imágenes adjuntas

**Respuesta**:
```json
{
  "success": true,
  "anuncio": {
    "id": 42,
    "titulo": "Bicicleta de montaña",
    "descripcion": "Bicicleta en buen estado...",
    "tipo": "venta",
    "categoria": {
      "id": 5,
      "slug": "deportes",
      "nombre": "Deportes"
    },
    "precio": 150.00,
    "estado": "bueno",
    "ubicacion": "Madrid",
    "vendido": false,
    "imagen": "",
    "fecha_publicacion": "2024-01-20T14:20:00+00:00",
    "autor": {
      "id": 10,
      "nombre": "Tu Nombre"
    }
  },
  "mensaje": "Anuncio publicado con éxito"
}
```

**cURL**:
```bash
curl -X POST "https://tu-sitio.com/wp-json/flavor-platform/v1/marketplace/anuncios" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "titulo": "Bicicleta de montaña",
    "descripcion": "Bicicleta en buen estado...",
    "tipo": "venta",
    "categoria": "deportes",
    "precio": 150.00,
    "estado": "bueno",
    "ubicacion": "Madrid"
  }'
```

---

### 3. Mis Anuncios

Lista los anuncios que has publicado.

```http
GET /marketplace/mis-anuncios
```

**🔒 Requiere autenticación**

**Query Parameters**:
- `estado` (string, opcional): `publish`, `draft`, `todos` (default: publish)

**Respuesta**:
```json
{
  "success": true,
  "anuncios": [
    {
      "id": 42,
      "titulo": "Bicicleta de montaña",
      "descripcion": "...",
      "tipo": "venta",
      "precio": 150.00,
      "vendido": false,
      "imagen": "...",
      "fecha_publicacion": "2024-01-20T14:20:00+00:00",
      "autor": {
        "id": 10,
        "nombre": "Tu Nombre"
      }
    }
  ],
  "total": 5
}
```

---

### 4. Detalle de Anuncio

Obtiene el detalle completo de un anuncio.

```http
GET /marketplace/anuncios/{id}
```

**URL Parameters**:
- `id`: ID del anuncio

**Respuesta**:
```json
{
  "success": true,
  "anuncio": {
    "id": 42,
    "titulo": "Bicicleta de montaña",
    "descripcion": "Descripción completa...",
    "tipo": "venta",
    "categoria": {
      "id": 5,
      "slug": "deportes",
      "nombre": "Deportes"
    },
    "precio": 150.00,
    "estado": "bueno",
    "ubicacion": "Madrid",
    "vendido": false,
    "imagen": "...",
    "imagenes": [
      "https://tu-sitio.com/wp-content/uploads/bici1.jpg",
      "https://tu-sitio.com/wp-content/uploads/bici2.jpg"
    ],
    "vistas": 45,
    "fecha_publicacion": "2024-01-15T10:30:00+00:00",
    "fecha_modificacion": "2024-01-15T10:30:00+00:00",
    "autor": {
      "id": 10,
      "nombre": "Juan Pérez"
    }
  }
}
```

---

### 5. Actualizar Anuncio

Actualiza un anuncio existente.

```http
PUT /marketplace/anuncios/{id}
```

**🔒 Requiere autenticación** (solo el autor)

**URL Parameters**:
- `id`: ID del anuncio

**Body** (JSON - todos los campos opcionales):
```json
{
  "titulo": "Bicicleta de montaña - REBAJADA",
  "descripcion": "Descripción actualizada",
  "tipo": "venta",
  "categoria": "deportes",
  "precio": 120.00,
  "estado": "bueno",
  "ubicacion": "Madrid Centro"
}
```

**Respuesta**:
```json
{
  "success": true,
  "anuncio": {
    "id": 42,
    "titulo": "Bicicleta de montaña - REBAJADA",
    "precio": 120.00,
    ...
  },
  "mensaje": "Anuncio actualizado con éxito"
}
```

---

### 6. Eliminar Anuncio

Elimina (mueve a papelera) un anuncio.

```http
DELETE /marketplace/anuncios/{id}
```

**🔒 Requiere autenticación** (solo el autor)

**URL Parameters**:
- `id`: ID del anuncio

**Respuesta**:
```json
{
  "success": true,
  "mensaje": "Anuncio eliminado con éxito"
}
```

---

### 7. Listar Categorías

Obtiene las categorías disponibles.

```http
GET /marketplace/categorias
```

**Respuesta**:
```json
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "slug": "electronica",
      "nombre": "Electrónica",
      "descripcion": "",
      "count": 12
    },
    {
      "id": 2,
      "slug": "muebles",
      "nombre": "Muebles",
      "descripcion": "",
      "count": 8
    },
    {
      "id": 3,
      "slug": "ropa",
      "nombre": "Ropa",
      "descripcion": "",
      "count": 25
    },
    {
      "id": 4,
      "slug": "libros",
      "nombre": "Libros",
      "descripcion": "",
      "count": 15
    },
    {
      "id": 5,
      "slug": "deportes",
      "nombre": "Deportes",
      "descripcion": "",
      "count": 10
    }
  ]
}
```

---

### 8. Contactar con Vendedor

Envía un mensaje al vendedor de un anuncio.

```http
POST /marketplace/anuncios/{id}/contactar
```

**🔒 Requiere autenticación**

**URL Parameters**:
- `id`: ID del anuncio

**Body** (JSON):
```json
{
  "mensaje": "Hola, ¿todavía está disponible la bicicleta? ¿Puedo verla mañana?"
}
```

**Respuesta**:
```json
{
  "success": true,
  "mensaje": "Mensaje enviado al vendedor"
}
```

---

### 9. Marcar como Vendido

Marca un anuncio como vendido/entregado.

```http
POST /marketplace/anuncios/{id}/marcar-vendido
```

**🔒 Requiere autenticación** (solo el autor)

**URL Parameters**:
- `id`: ID del anuncio

**Respuesta**:
```json
{
  "success": true,
  "mensaje": "Anuncio marcado como vendido"
}
```

---

## Tipos de Anuncio

| Tipo | Descripción | Requiere Precio |
|------|-------------|-----------------|
| `regalo` | Artículo gratis | No |
| `venta` | Venta por dinero | Sí |
| `cambio` | Intercambio por otro artículo | No |
| `alquiler` | Alquiler temporal | Sí |

## Estados de Conservación

- `nuevo`: Nuevo, sin usar
- `como_nuevo`: Como nuevo, muy poco uso
- `bueno`: Buen estado
- `aceptable`: Estado aceptable, señales de uso

## Códigos de Estado HTTP

- **200 OK**: Solicitud exitosa
- **201 Created**: Anuncio creado
- **400 Bad Request**: Datos inválidos
- **401 Unauthorized**: No autenticado
- **403 Forbidden**: Sin permisos
- **404 Not Found**: Anuncio no encontrado
- **500 Internal Server Error**: Error del servidor

---

## Flujo de Uso

### 1. Usuario Publica Anuncio

```
Usuario → POST /anuncios
        ← Anuncio publicado
```

### 2. Otros Usuarios Buscan

```
Usuario B → GET /anuncios?tipo=venta&categoria=deportes
          ← Lista de anuncios

Usuario B → GET /anuncios/42
          ← Detalle completo
```

### 3. Usuario B Contacta

```
Usuario B → POST /anuncios/42/contactar
          ← Mensaje enviado
```

### 4. Usuario A Marca como Vendido

```
Usuario A → POST /anuncios/42/marcar-vendido
          ← Marcado como vendido
```

---

## Integración en Flutter

### Servicio Dart

```dart
class MarketplaceService {
  final Dio _dio;
  final String baseUrl = 'https://tu-sitio.com/wp-json/flavor-platform/v1/marketplace';

  // Listar anuncios
  Future<PaginatedAnuncios> getAnuncios({
    String? busqueda,
    String tipo = 'todos',
    String? categoria,
    int limite = 20,
    int pagina = 1,
  }) async {
    final response = await _dio.get('$baseUrl/anuncios', queryParameters: {
      if (busqueda != null) 'busqueda': busqueda,
      'tipo': tipo,
      if (categoria != null) 'categoria': categoria,
      'limite': limite,
      'pagina': pagina,
    });

    return PaginatedAnuncios.fromJson(response.data);
  }

  // Crear anuncio
  Future<Anuncio> crearAnuncio({
    required String titulo,
    required String descripcion,
    required String tipo,
    String? categoria,
    double? precio,
    String? estado,
    String? ubicacion,
  }) async {
    final response = await _dio.post(
      '$baseUrl/anuncios',
      data: {
        'titulo': titulo,
        'descripcion': descripcion,
        'tipo': tipo,
        if (categoria != null) 'categoria': categoria,
        if (precio != null) 'precio': precio,
        if (estado != null) 'estado': estado,
        if (ubicacion != null) 'ubicacion': ubicacion,
      },
    );

    return Anuncio.fromJson(response.data['anuncio']);
  }

  // Mis anuncios
  Future<List<Anuncio>> getMisAnuncios({String estado = 'publish'}) async {
    final response = await _dio.get(
      '$baseUrl/mis-anuncios',
      queryParameters: {'estado': estado},
    );

    final List anuncios = response.data['anuncios'];
    return anuncios.map((json) => Anuncio.fromJson(json)).toList();
  }

  // Detalle de anuncio
  Future<Anuncio> getAnuncio(int id) async {
    final response = await _dio.get('$baseUrl/anuncios/$id');
    return Anuncio.fromJson(response.data['anuncio']);
  }

  // Actualizar anuncio
  Future<Anuncio> actualizarAnuncio({
    required int id,
    String? titulo,
    String? descripcion,
    String? tipo,
    String? categoria,
    double? precio,
    String? estado,
    String? ubicacion,
  }) async {
    final response = await _dio.put(
      '$baseUrl/anuncios/$id',
      data: {
        if (titulo != null) 'titulo': titulo,
        if (descripcion != null) 'descripcion': descripcion,
        if (tipo != null) 'tipo': tipo,
        if (categoria != null) 'categoria': categoria,
        if (precio != null) 'precio': precio,
        if (estado != null) 'estado': estado,
        if (ubicacion != null) 'ubicacion': ubicacion,
      },
    );

    return Anuncio.fromJson(response.data['anuncio']);
  }

  // Eliminar anuncio
  Future<void> eliminarAnuncio(int id) async {
    await _dio.delete('$baseUrl/anuncios/$id');
  }

  // Contactar vendedor
  Future<void> contactarVendedor({
    required int anuncioId,
    required String mensaje,
  }) async {
    await _dio.post(
      '$baseUrl/anuncios/$anuncioId/contactar',
      data: {'mensaje': mensaje},
    );
  }

  // Marcar como vendido
  Future<void> marcarVendido(int id) async {
    await _dio.post('$baseUrl/anuncios/$id/marcar-vendido');
  }

  // Listar categorías
  Future<List<Categoria>> getCategorias() async {
    final response = await _dio.get('$baseUrl/categorias');
    final List categorias = response.data['categorias'];
    return categorias.map((json) => Categoria.fromJson(json)).toList();
  }
}
```

### Modelos Dart

```dart
class Anuncio {
  final int id;
  final String titulo;
  final String descripcion;
  final String tipo;
  final Categoria? categoria;
  final double? precio;
  final String? estado;
  final String? ubicacion;
  final bool vendido;
  final String? imagen;
  final List<String>? imagenes;
  final int? vistas;
  final DateTime fechaPublicacion;
  final DateTime? fechaModificacion;
  final Autor autor;

  Anuncio({
    required this.id,
    required this.titulo,
    required this.descripcion,
    required this.tipo,
    this.categoria,
    this.precio,
    this.estado,
    this.ubicacion,
    required this.vendido,
    this.imagen,
    this.imagenes,
    this.vistas,
    required this.fechaPublicacion,
    this.fechaModificacion,
    required this.autor,
  });

  factory Anuncio.fromJson(Map<String, dynamic> json) {
    return Anuncio(
      id: json['id'],
      titulo: json['titulo'],
      descripcion: json['descripcion'],
      tipo: json['tipo'],
      categoria: json['categoria'] != null
          ? Categoria.fromJson(json['categoria'])
          : null,
      precio: json['precio']?.toDouble(),
      estado: json['estado'],
      ubicacion: json['ubicacion'],
      vendido: json['vendido'] ?? false,
      imagen: json['imagen'],
      imagenes: json['imagenes'] != null
          ? List<String>.from(json['imagenes'])
          : null,
      vistas: json['vistas'],
      fechaPublicacion: DateTime.parse(json['fecha_publicacion']),
      fechaModificacion: json['fecha_modificacion'] != null
          ? DateTime.parse(json['fecha_modificacion'])
          : null,
      autor: Autor.fromJson(json['autor']),
    );
  }

  String get tipoPretty {
    switch (tipo) {
      case 'regalo':
        return 'Regalo';
      case 'venta':
        return 'Venta';
      case 'cambio':
        return 'Cambio';
      case 'alquiler':
        return 'Alquiler';
      default:
        return tipo;
    }
  }

  String get precioFormatted {
    if (precio == null) return tipo == 'regalo' ? 'Gratis' : 'A consultar';
    return '${precio!.toStringAsFixed(2)}€';
  }
}

class Categoria {
  final int id;
  final String slug;
  final String nombre;
  final String? descripcion;
  final int? count;

  Categoria({
    required this.id,
    required this.slug,
    required this.nombre,
    this.descripcion,
    this.count,
  });

  factory Categoria.fromJson(Map<String, dynamic> json) {
    return Categoria(
      id: json['id'],
      slug: json['slug'],
      nombre: json['nombre'],
      descripcion: json['descripcion'],
      count: json['count'],
    );
  }
}

class Autor {
  final int id;
  final String nombre;

  Autor({
    required this.id,
    required this.nombre,
  });

  factory Autor.fromJson(Map<String, dynamic> json) {
    return Autor(
      id: json['id'],
      nombre: json['nombre'],
    );
  }
}

class PaginatedAnuncios {
  final List<Anuncio> anuncios;
  final int total;
  final int pagina;
  final int limite;
  final int totalPaginas;

  PaginatedAnuncios({
    required this.anuncios,
    required this.total,
    required this.pagina,
    required this.limite,
    required this.totalPaginas,
  });

  factory PaginatedAnuncios.fromJson(Map<String, dynamic> json) {
    return PaginatedAnuncios(
      anuncios: (json['anuncios'] as List)
          .map((a) => Anuncio.fromJson(a))
          .toList(),
      total: json['total'],
      pagina: json['pagina'],
      limite: json['limite'],
      totalPaginas: json['total_paginas'],
    );
  }

  bool get hasMore => pagina < totalPaginas;
}
```

---

## Testing

### Test con Postman/Insomnia

1. **Listar anuncios públicos**:
   ```
   GET {{base_url}}/marketplace/anuncios?tipo=venta
   ```

2. **Crear anuncio**:
   ```
   POST {{base_url}}/marketplace/anuncios
   Headers:
     Authorization: Bearer {{token}}
   Body:
     {
       "titulo": "Test Item",
       "descripcion": "Test",
       "tipo": "regalo"
     }
   ```

3. **Ver mis anuncios**:
   ```
   GET {{base_url}}/marketplace/mis-anuncios
   Headers:
     Authorization: Bearer {{token}}
   ```

---

## Notas Importantes

- Las fechas están en formato **ISO 8601**
- Los anuncios se mueven a papelera, no se eliminan permanentemente
- Solo el autor puede editar/eliminar sus anuncios
- El precio es opcional para regalo y cambio
- Las imágenes se incrementan en el contador de vistas
- Los anuncios expiran automáticamente después de 30 días (configurable)

---

**¡API lista para integrar en tu app Flutter!** 🚀
