# API REST - Grupos de Consumo para Apps Móviles

Documentación de endpoints REST para integrar el módulo de Grupos de Consumo en aplicaciones móviles (Flutter, React Native, etc.).

## Base URL

```
https://tu-dominio.com/wp-json/flavor-chat-ia/v1
```

## Autenticación

La mayoría de endpoints requieren autenticación mediante JWT o cookies de WordPress.

Para JWT, incluir header:
```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Listar Pedidos

**GET** `/pedidos`

Lista todos los pedidos disponibles.

**Parámetros de Query:**

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `estado` | string | `abierto` | Estado del pedido: `abierto`, `cerrado`, `recibido`, `repartido`, `todos` |
| `per_page` | int | `10` | Pedidos por página |
| `page` | int | `1` | Número de página |

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "titulo": "Pedido de Verduras Ecológicas",
      "descripcion": "Verduras frescas de temporada...",
      "productor": "Huerta Ecológica La Vega",
      "producto": "Verduras ecológicas",
      "precio_base": 12.00,
      "gastos_gestion": 10.0,
      "precio_final": 13.20,
      "unidad": "caja",
      "cantidad_minima": 20,
      "cantidad_maxima": 50,
      "cantidad_actual": 15,
      "progreso": 75.0,
      "estado": "abierto",
      "participantes_count": 8,
      "fecha_cierre": "2026-02-15 23:59:59",
      "fecha_entrega": "2026-02-17 18:00:00",
      "lugar_recogida": "Local de la Cooperativa, C/ Mayor 15",
      "imagen": "https://ejemplo.com/imagen.jpg"
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 5
  }
}
```

---

### 2. Obtener Pedido Específico

**GET** `/pedidos/{id}`

Obtiene información detallada de un pedido.

**Parámetros de URL:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del pedido |

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "titulo": "Pedido de Verduras Ecológicas",
    "descripcion": "Contenido completo del pedido...",
    "productor": "Huerta Ecológica La Vega",
    "producto": "Verduras ecológicas",
    "precio_base": 12.00,
    "gastos_gestion": 10.0,
    "precio_final": 13.20,
    "unidad": "caja",
    "cantidad_minima": 20,
    "cantidad_maxima": 50,
    "cantidad_actual": 15,
    "progreso": 75.0,
    "estado": "abierto",
    "participantes_count": 8,
    "fecha_cierre": "2026-02-15 23:59:59",
    "fecha_entrega": "2026-02-17 18:00:00",
    "lugar_recogida": "Local de la Cooperativa, C/ Mayor 15",
    "imagen": "https://ejemplo.com/imagen.jpg",
    "meta": {
      "contacto": "info@cooperativa.com",
      "instrucciones": "Traer bolsas reutilizables"
    }
  }
}
```

**Errores:**

- `404`: Pedido no encontrado

---

### 3. Unirse a Pedido

**POST** `/pedidos/{id}/unirse`

Añade al usuario autenticado a un pedido colectivo.

**🔒 Requiere autenticación**

**Parámetros de URL:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del pedido |

**Body (JSON):**

```json
{
  "cantidad": 2.5
}
```

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "message": "¡Te has unido al pedido!",
  "data": {
    "importe_total": 33.00,
    "pedido": {
      "id": 123,
      "titulo": "Pedido de Verduras Ecológicas",
      ...
    }
  }
}
```

**Errores:**

- `400`: Pedido cerrado, ya participas, o datos inválidos
- `401`: No autenticado

---

### 4. Mis Pedidos

**GET** `/mis-pedidos`

Lista los pedidos en los que participa el usuario autenticado.

**🔒 Requiere autenticación**

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "titulo": "Pedido de Verduras Ecológicas",
      "productor": "Huerta Ecológica La Vega",
      "producto": "Verduras ecológicas",
      "precio_final": 13.20,
      "estado": "abierto",
      "fecha_entrega": "2026-02-17 18:00:00",
      "lugar_recogida": "Local de la Cooperativa",
      "mi_participacion": {
        "cantidad": 2,
        "importe": 26.40,
        "pagado": false,
        "recogido": false,
        "fecha_inscripcion": "2026-02-01 10:30:00"
      }
    }
  ],
  "total": 1
}
```

---

### 5. Marcar como Pagado

**POST** `/pedidos/{id}/marcar-pagado`

Marca el pedido del usuario como pagado.

**🔒 Requiere autenticación**

**Parámetros de URL:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del pedido |

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "message": "Marcado como pagado"
}
```

**Errores:**

- `400`: No participas en este pedido
- `401`: No autenticado

---

### 6. Marcar como Recogido

**POST** `/pedidos/{id}/marcar-recogido`

Marca el pedido del usuario como recogido.

**🔒 Requiere autenticación**

**Parámetros de URL:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del pedido |

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "message": "Marcado como recogido"
}
```

**Errores:**

- `400`: No participas en este pedido
- `401`: No autenticado

---

## Códigos de Estado HTTP

| Código | Significado |
|--------|-------------|
| `200` | Operación exitosa |
| `400` | Solicitud inválida |
| `401` | No autenticado |
| `404` | Recurso no encontrado |
| `500` | Error del servidor |

---

## Ejemplo de Integración (Flutter/Dart)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class GruposConsumoService {
  final String baseUrl = 'https://tu-dominio.com/wp-json/flavor-chat-ia/v1';
  String? _authToken;

  // Listar pedidos abiertos
  Future<List<Pedido>> getPedidosAbiertos() async {
    final response = await http.get(
      Uri.parse('$baseUrl/pedidos?estado=abierto'),
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((json) => Pedido.fromJson(json))
          .toList();
    }
    throw Exception('Error al cargar pedidos');
  }

  // Unirse a pedido
  Future<void> unirsePedido(int pedidoId, double cantidad) async {
    final response = await http.post(
      Uri.parse('$baseUrl/pedidos/$pedidoId/unirse'),
      headers: {
        'Authorization': 'Bearer $_authToken',
        'Content-Type': 'application/json',
      },
      body: json.encode({'cantidad': cantidad}),
    );

    if (response.statusCode != 200) {
      final error = json.decode(response.body);
      throw Exception(error['message'] ?? 'Error al unirse');
    }
  }

  // Mis pedidos
  Future<List<MiPedido>> getMisPedidos() async {
    final response = await http.get(
      Uri.parse('$baseUrl/mis-pedidos'),
      headers: {'Authorization': 'Bearer $_authToken'},
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((json) => MiPedido.fromJson(json))
          .toList();
    }
    throw Exception('Error al cargar mis pedidos');
  }

  // Marcar como pagado
  Future<void> marcarPagado(int pedidoId) async {
    await http.post(
      Uri.parse('$baseUrl/pedidos/$pedidoId/marcar-pagado'),
      headers: {'Authorization': 'Bearer $_authToken'},
    );
  }
}

// Modelos de datos
class Pedido {
  final int id;
  final String titulo;
  final String productor;
  final double precioFinal;
  final int cantidadMinima;
  final double cantidadActual;
  final double progreso;

  Pedido.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        titulo = json['titulo'],
        productor = json['productor'],
        precioFinal = json['precio_final'].toDouble(),
        cantidadMinima = json['cantidad_minima'],
        cantidadActual = json['cantidad_actual'].toDouble(),
        progreso = json['progreso'].toDouble();
}
```

---

## Notas Importantes

1. **Autenticación**: Implementar JWT o usar cookies de WordPress
2. **Fechas**: Todas las fechas están en formato MySQL (`Y-m-d H:i:s`)
3. **Precios**: En euros, formato decimal
4. **Progreso**: Porcentaje de 0 a 100
5. **Estados**: `abierto`, `cerrado`, `recibido`, `repartido`, `cancelado`

---

## Testing

Prueba los endpoints con cURL:

```bash
# Listar pedidos
curl https://tu-dominio.com/wp-json/flavor-chat-ia/v1/pedidos

# Obtener pedido específico
curl https://tu-dominio.com/wp-json/flavor-chat-ia/v1/pedidos/123

# Unirse a pedido (requiere auth)
curl -X POST https://tu-dominio.com/wp-json/flavor-chat-ia/v1/pedidos/123/unirse \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"cantidad": 2}'
```

---

## Soporte

Para más información o reportar problemas, contactar al equipo de desarrollo.
