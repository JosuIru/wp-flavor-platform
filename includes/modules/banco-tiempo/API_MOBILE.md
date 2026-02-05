# 📱 API Móvil - Banco de Tiempo

## Descripción

API REST optimizada para aplicaciones móviles del módulo **Banco de Tiempo**.

Sistema de intercambio de servicios donde el tiempo es la moneda. Una hora siempre vale lo mismo, independientemente del servicio.

## Base URL

```
https://tu-sitio.com/wp-json/flavor-chat-ia/v1/banco-tiempo
```

## Autenticación

Endpoints que requieren autenticación usan **JWT** o **sesión de WordPress**.

Header requerido:
```
Authorization: Bearer {tu-token-jwt}
```

O cookies de sesión de WordPress.

## Endpoints

### 1. Listar Servicios Disponibles

Obtiene servicios que otros usuarios han publicado.

```http
GET /banco-tiempo/servicios
```

**Query Parameters**:
- `busqueda` (string, opcional): Búsqueda en título y descripción
- `categoria` (string, opcional): Filtrar por categoría
- `limite` (int, opcional): Resultados por página (default: 20)
- `pagina` (int, opcional): Número de página (default: 1)

**Respuesta**:
```json
{
  "success": true,
  "servicios": [
    {
      "id": 1,
      "titulo": "Clases de guitarra",
      "descripcion": "Ofrezco clases de guitarra para principiantes",
      "categoria": "educacion",
      "horas_estimadas": 1.5,
      "estado": "activo",
      "fecha_publicacion": "2024-01-15T10:30:00+00:00",
      "usuario": {
        "id": 42,
        "nombre": "María García",
        "email": "maria@ejemplo.com"
      }
    }
  ],
  "total": 25,
  "pagina": 1,
  "limite": 20,
  "total_paginas": 2
}
```

**cURL**:
```bash
curl "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios?categoria=educacion&limite=10"
```

---

### 2. Crear Servicio

Publica un nuevo servicio que ofreces.

```http
POST /banco-tiempo/servicios
```

**🔒 Requiere autenticación**

**Body** (JSON):
```json
{
  "titulo": "Paseo de perros",
  "descripcion": "Paseo tu perro por el barrio durante 1 hora",
  "categoria": "cuidados",
  "horas_estimadas": 1.0
}
```

**Respuesta**:
```json
{
  "success": true,
  "servicio": {
    "id": 15,
    "titulo": "Paseo de perros",
    "descripcion": "Paseo tu perro...",
    "categoria": "cuidados",
    "horas_estimadas": 1.0,
    "estado": "activo",
    "fecha_publicacion": "2024-01-20T14:20:00+00:00",
    "usuario": {
      "id": 42,
      "nombre": "Tu Nombre"
    }
  },
  "mensaje": "Servicio publicado con éxito"
}
```

**cURL**:
```bash
curl -X POST "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "titulo": "Paseo de perros",
    "descripcion": "Paseo tu perro por el barrio durante 1 hora",
    "categoria": "cuidados",
    "horas_estimadas": 1.0
  }'
```

---

### 3. Mis Servicios

Lista los servicios que has publicado.

```http
GET /banco-tiempo/mis-servicios
```

**🔒 Requiere autenticación**

**Query Parameters**:
- `estado` (string, opcional): `activo`, `inactivo`, `todos` (default: todos)

**Respuesta**:
```json
{
  "success": true,
  "servicios": [
    {
      "id": 15,
      "titulo": "Paseo de perros",
      "descripcion": "...",
      "categoria": "cuidados",
      "horas_estimadas": 1.0,
      "estado": "activo",
      "fecha_publicacion": "2024-01-20T14:20:00+00:00",
      "usuario": {
        "id": 42,
        "nombre": "Tu Nombre"
      }
    }
  ],
  "total": 3
}
```

---

### 4. Ver Saldo

Muestra el saldo de horas del usuario.

```http
GET /banco-tiempo/saldo
```

**🔒 Requiere autenticación**

**Respuesta**:
```json
{
  "success": true,
  "saldo": {
    "horas_ganadas": 15.5,
    "horas_gastadas": 8.0,
    "saldo_actual": 7.5,
    "pendientes": 2,
    "servicios_activos": 3
  }
}
```

**Explicación**:
- `horas_ganadas`: Horas que has ganado prestando servicios
- `horas_gastadas`: Horas que has gastado recibiendo servicios
- `saldo_actual`: Diferencia (ganadas - gastadas)
- `pendientes`: Transacciones pendientes de completar
- `servicios_activos`: Servicios que tienes publicados

---

### 5. Historial de Transacciones

Lista el historial de intercambios.

```http
GET /banco-tiempo/transacciones
```

**🔒 Requiere autenticación**

**Query Parameters**:
- `tipo` (string, opcional): `todos`, `recibidas`, `ofrecidas` (default: todos)
- `estado` (string, opcional): `pendiente`, `aceptado`, `completado`, `cancelado`, `todos`
- `limite` (int, opcional): Número de resultados (default: 20)

**Respuesta**:
```json
{
  "success": true,
  "transacciones": [
    {
      "id": 5,
      "servicio_id": 10,
      "horas": 2.0,
      "estado": "completado",
      "mensaje": "¿Podrías ayudarme este sábado?",
      "fecha_preferida": "2024-01-25",
      "fecha_solicitud": "2024-01-20T10:00:00+00:00",
      "fecha_completado": "2024-01-25T16:00:00+00:00",
      "valoracion": 5,
      "comentario": "Excelente servicio",
      "tipo": "ofrecida",
      "solicitante": {
        "id": 15,
        "nombre": "Juan Pérez"
      },
      "receptor": {
        "id": 42,
        "nombre": "Tu Nombre"
      }
    }
  ],
  "total": 12
}
```

---

### 6. Solicitar Servicio

Solicita un servicio a otro usuario.

```http
POST /banco-tiempo/servicios/{id}/solicitar
```

**🔒 Requiere autenticación**

**URL Parameters**:
- `id`: ID del servicio

**Body** (JSON):
```json
{
  "mensaje": "Hola, ¿podrías ayudarme este sábado?",
  "fecha_preferida": "2024-01-25"
}
```

**Respuesta**:
```json
{
  "success": true,
  "transaccion": {
    "id": 20,
    "servicio_id": 10,
    "horas": 1.5,
    "estado": "pendiente",
    "mensaje": "Hola, ¿podrías ayudarme...",
    "fecha_preferida": "2024-01-25",
    "fecha_solicitud": "2024-01-20T12:00:00+00:00",
    "tipo": "recibida",
    "solicitante": {
      "id": 42,
      "nombre": "Tu Nombre"
    },
    "receptor": {
      "id": 10,
      "nombre": "María García"
    }
  },
  "mensaje": "Solicitud enviada con éxito"
}
```

**cURL**:
```bash
curl -X POST "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/banco-tiempo/servicios/10/solicitar" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "mensaje": "Hola, ¿podrías ayudarme este sábado?",
    "fecha_preferida": "2024-01-25"
  }'
```

---

### 7. Completar Intercambio

Marca un intercambio como completado y transfiere las horas.

```http
POST /banco-tiempo/transacciones/{id}/completar
```

**🔒 Requiere autenticación** (solo el receptor)

**URL Parameters**:
- `id`: ID de la transacción

**Body** (JSON):
```json
{
  "horas_reales": 2.0,
  "valoracion": 5,
  "comentario": "Excelente trabajo, muy profesional"
}
```

**Campos**:
- `horas_reales` (number, requerido): Horas reales que tomó el servicio
- `valoracion` (int, 1-5, opcional): Valoración del servicio
- `comentario` (string, opcional): Comentario sobre el servicio

**Respuesta**:
```json
{
  "success": true,
  "mensaje": "Intercambio completado con éxito"
}
```

---

### 8. Eliminar Servicio

Elimina (desactiva) un servicio que publicaste.

```http
DELETE /banco-tiempo/servicios/{id}
```

**🔒 Requiere autenticación**

**URL Parameters**:
- `id`: ID del servicio

**Respuesta**:
```json
{
  "success": true,
  "mensaje": "Servicio eliminado con éxito"
}
```

---

### 9. Listar Categorías

Obtiene las categorías de servicios disponibles.

```http
GET /banco-tiempo/categorias
```

**Respuesta**:
```json
{
  "success": true,
  "categorias": [
    {
      "id": "cuidados",
      "nombre": "Cuidados",
      "icon": "favorite"
    },
    {
      "id": "educacion",
      "nombre": "Educación",
      "icon": "school"
    },
    {
      "id": "bricolaje",
      "nombre": "Bricolaje",
      "icon": "build"
    },
    {
      "id": "tecnologia",
      "nombre": "Tecnología",
      "icon": "computer"
    },
    {
      "id": "transporte",
      "nombre": "Transporte",
      "icon": "directions_car"
    },
    {
      "id": "otros",
      "nombre": "Otros",
      "icon": "more_horiz"
    }
  ]
}
```

---

## Flujo de Uso

### 1. Usuario Ofrece Servicio

```
Usuario → POST /servicios
        ← Servicio publicado
```

### 2. Otro Usuario Solicita Servicio

```
Usuario B → GET /servicios?categoria=educacion
          ← Lista de servicios

Usuario B → POST /servicios/15/solicitar
          ← Solicitud enviada
```

### 3. Usuario Original Completa Servicio

```
Usuario A → GET /transacciones?tipo=ofrecidas
          ← Ve solicitudes pendientes

[Realiza el servicio]

Usuario A → POST /transacciones/20/completar
          ← Intercambio completado
          ← Horas transferidas
```

### 4. Verificar Saldo

```
Usuario A → GET /saldo
          ← Saldo actualizado con nuevas horas
```

---

## Códigos de Estado HTTP

- **200 OK**: Solicitud exitosa
- **201 Created**: Recurso creado
- **400 Bad Request**: Datos inválidos
- **401 Unauthorized**: No autenticado
- **403 Forbidden**: Sin permisos
- **404 Not Found**: Recurso no encontrado
- **500 Internal Server Error**: Error del servidor

---

## Manejo de Errores

**Formato de error**:
```json
{
  "code": "codigo_error",
  "message": "Descripción del error",
  "data": {
    "status": 400
  }
}
```

**Errores comunes**:

| Código | Mensaje | Causa |
|--------|---------|-------|
| `no_auth` | Debes iniciar sesión | No autenticado |
| `datos_incompletos` | Campos obligatorios faltantes | Validación |
| `servicio_no_encontrado` | El servicio no existe | ID inválido |
| `servicio_propio` | No puedes solicitar tu propio servicio | Lógica |
| `sin_permiso` | No tienes permiso | Acción no autorizada |
| `horas_invalidas` | Horas fuera de rango | Validación |

---

## Integración en Flutter

### Servicio Dart

```dart
class BancoTiempoService {
  final Dio _dio;
  final String baseUrl = 'https://tu-sitio.com/wp-json/flavor-chat-ia/v1/banco-tiempo';

  // Listar servicios
  Future<List<Servicio>> getServicios({
    String? busqueda,
    String? categoria,
    int limite = 20,
    int pagina = 1,
  }) async {
    final response = await _dio.get('$baseUrl/servicios', queryParameters: {
      if (busqueda != null) 'busqueda': busqueda,
      if (categoria != null) 'categoria': categoria,
      'limite': limite,
      'pagina': pagina,
    });

    final List servicios = response.data['servicios'];
    return servicios.map((json) => Servicio.fromJson(json)).toList();
  }

  // Crear servicio
  Future<Servicio> crearServicio({
    required String titulo,
    required String descripcion,
    required String categoria,
    required double horasEstimadas,
  }) async {
    final response = await _dio.post(
      '$baseUrl/servicios',
      data: {
        'titulo': titulo,
        'descripcion': descripcion,
        'categoria': categoria,
        'horas_estimadas': horasEstimadas,
      },
    );

    return Servicio.fromJson(response.data['servicio']);
  }

  // Ver saldo
  Future<Saldo> getSaldo() async {
    final response = await _dio.get('$baseUrl/saldo');
    return Saldo.fromJson(response.data['saldo']);
  }

  // Solicitar servicio
  Future<Transaccion> solicitarServicio({
    required int servicioId,
    String? mensaje,
    String? fechaPreferida,
  }) async {
    final response = await _dio.post(
      '$baseUrl/servicios/$servicioId/solicitar',
      data: {
        if (mensaje != null) 'mensaje': mensaje,
        if (fechaPreferida != null) 'fecha_preferida': fechaPreferida,
      },
    );

    return Transaccion.fromJson(response.data['transaccion']);
  }

  // Completar intercambio
  Future<void> completarTransaccion({
    required int transaccionId,
    required double horasReales,
    int? valoracion,
    String? comentario,
  }) async {
    await _dio.post(
      '$baseUrl/transacciones/$transaccionId/completar',
      data: {
        'horas_reales': horasReales,
        if (valoracion != null) 'valoracion': valoracion,
        if (comentario != null) 'comentario': comentario,
      },
    );
  }

  // Listar transacciones
  Future<List<Transaccion>> getTransacciones({
    String tipo = 'todos',
    String estado = 'todos',
    int limite = 20,
  }) async {
    final response = await _dio.get('$baseUrl/transacciones', queryParameters: {
      'tipo': tipo,
      'estado': estado,
      'limite': limite,
    });

    final List transacciones = response.data['transacciones'];
    return transacciones.map((json) => Transaccion.fromJson(json)).toList();
  }
}
```

### Modelos Dart

```dart
class Servicio {
  final int id;
  final String titulo;
  final String descripcion;
  final String categoria;
  final double horasEstimadas;
  final String estado;
  final DateTime fechaPublicacion;
  final Usuario usuario;

  Servicio({
    required this.id,
    required this.titulo,
    required this.descripcion,
    required this.categoria,
    required this.horasEstimadas,
    required this.estado,
    required this.fechaPublicacion,
    required this.usuario,
  });

  factory Servicio.fromJson(Map<String, dynamic> json) {
    return Servicio(
      id: json['id'],
      titulo: json['titulo'],
      descripcion: json['descripcion'],
      categoria: json['categoria'],
      horasEstimadas: json['horas_estimadas'].toDouble(),
      estado: json['estado'],
      fechaPublicacion: DateTime.parse(json['fecha_publicacion']),
      usuario: Usuario.fromJson(json['usuario']),
    );
  }
}

class Saldo {
  final double horasGanadas;
  final double horasGastadas;
  final double saldoActual;
  final int pendientes;
  final int serviciosActivos;

  Saldo({
    required this.horasGanadas,
    required this.horasGastadas,
    required this.saldoActual,
    required this.pendientes,
    required this.serviciosActivos,
  });

  factory Saldo.fromJson(Map<String, dynamic> json) {
    return Saldo(
      horasGanadas: json['horas_ganadas'].toDouble(),
      horasGastadas: json['horas_gastadas'].toDouble(),
      saldoActual: json['saldo_actual'].toDouble(),
      pendientes: json['pendientes'],
      serviciosActivos: json['servicios_activos'],
    );
  }
}

class Transaccion {
  final int id;
  final int servicioId;
  final double horas;
  final String estado;
  final String? mensaje;
  final String? fechaPreferida;
  final DateTime fechaSolicitud;
  final DateTime? fechaCompletado;
  final int? valoracion;
  final String? comentario;
  final String tipo; // 'ofrecida' o 'recibida'
  final Usuario solicitante;
  final Usuario receptor;

  Transaccion({
    required this.id,
    required this.servicioId,
    required this.horas,
    required this.estado,
    this.mensaje,
    this.fechaPreferida,
    required this.fechaSolicitud,
    this.fechaCompletado,
    this.valoracion,
    this.comentario,
    required this.tipo,
    required this.solicitante,
    required this.receptor,
  });

  factory Transaccion.fromJson(Map<String, dynamic> json) {
    return Transaccion(
      id: json['id'],
      servicioId: json['servicio_id'],
      horas: json['horas'].toDouble(),
      estado: json['estado'],
      mensaje: json['mensaje'],
      fechaPreferida: json['fecha_preferida'],
      fechaSolicitud: DateTime.parse(json['fecha_solicitud']),
      fechaCompletado: json['fecha_completado'] != null
          ? DateTime.parse(json['fecha_completado'])
          : null,
      valoracion: json['valoracion'],
      comentario: json['comentario'],
      tipo: json['tipo'],
      solicitante: Usuario.fromJson(json['solicitante']),
      receptor: Usuario.fromJson(json['receptor']),
    );
  }
}

class Usuario {
  final int id;
  final String nombre;
  final String? email;

  Usuario({
    required this.id,
    required this.nombre,
    this.email,
  });

  factory Usuario.fromJson(Map<String, dynamic> json) {
    return Usuario(
      id: json['id'],
      nombre: json['nombre'],
      email: json['email'],
    );
  }
}
```

---

## Testing

### Test con Postman/Insomnia

1. **Crear servicio**:
   ```
   POST {{base_url}}/banco-tiempo/servicios
   Headers:
     Authorization: Bearer {{token}}
   Body:
     {
       "titulo": "Test Service",
       "descripcion": "Test",
       "categoria": "otros",
       "horas_estimadas": 1
     }
   ```

2. **Listar servicios**:
   ```
   GET {{base_url}}/banco-tiempo/servicios?categoria=todos
   ```

3. **Ver saldo**:
   ```
   GET {{base_url}}/banco-tiempo/saldo
   Headers:
     Authorization: Bearer {{token}}
   ```

---

## Notas Importantes

- Las fechas están en formato **ISO 8601** (compatible con DateTime de Dart)
- Los IDs son números enteros
- Las horas pueden ser decimales (ej: 1.5 = 1 hora y 30 minutos)
- Los servicios se marcan como "inactivos" en lugar de eliminarse
- Solo el receptor puede completar una transacción
- No puedes solicitar tus propios servicios

---

**¡API lista para integrar en tu app Flutter!** 🚀
