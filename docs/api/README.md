# Flavor Platform REST API

## Documentación Principal

| Documento | Descripción | Audiencia |
|-----------|-------------|-----------|
| **[CLAUDE-API-GUIDE.md](CLAUDE-API-GUIDE.md)** | Guía completa de APIs para automatización | Claude Code / Desarrolladores |
| **[WORKFLOW-CREAR-SITIO.md](WORKFLOW-CREAR-SITIO.md)** | Tutorial paso a paso para crear un sitio | Claude Code / Desarrolladores |
| **[ENDPOINTS-REFERENCE.md](ENDPOINTS-REFERENCE.md)** | Referencia técnica de 100+ endpoints | Desarrolladores |
| **[../GUIA-ADMINISTRADOR.md](../GUIA-ADMINISTRADOR.md)** | Manual completo de administración | Administradores |

---

## Introducción

La API REST de Flavor Platform proporciona acceso programático a todas las funcionalidades de la plataforma, incluyendo:

- **Site Builder**: Creación automática de sitios completos
- **Visual Builder Pro (VBP)**: Creación de páginas visuales con bloques
- **Módulos**: 60+ módulos activables (eventos, socios, foros, marketplace, etc.)
- **Grupos de Consumo**: Pedidos colectivos, productos, productores
- **Red de Comunidades**: Directorio, mapa, tablón y colaboraciones
- **SEO**: Metadatos, Open Graph, Schema.org
- **Apps Móviles**: Configuración para Flutter

## URL Base

```
https://tu-sitio.com/wp-json/
```

## Namespaces Disponibles

| Namespace | Descripción | Endpoints |
|-----------|-------------|-----------|
| `flavor-site-builder/v1` | Orquestador para crear sitios | 17 |
| `flavor-vbp/v1` | VBP, Site Config, Modules, Media, SEO, App | 78 |
| `flavor-chat-ia/v1` | Grupos de consumo y funcionalidades principales | 20+ |
| `flavor-network/v1` | Red de comunidades | 15+ |
| `flavor/v1` | PWA, temas, dashboard cliente | 10+ |
| `chat-ia-mobile/v1` | API móvil Flutter | 10+ |

## Autenticacion

### Bearer Token (JWT)

Para aplicaciones moviles y clientes externos, usa autenticacion JWT:

```bash
curl -X GET "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/mis-pedidos" \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

### WordPress Nonce

Para peticiones desde el frontend de WordPress:

```javascript
fetch('/wp-json/flavor-chat-ia/v1/gc/perfil', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

### Obtener Token JWT

```bash
curl -X POST "https://tu-sitio.com/wp-json/jwt-auth/v1/token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "usuario",
    "password": "contrasena"
  }'
```

## Rate Limiting

| Tipo de Usuario | Limite |
|-----------------|--------|
| Autenticado | 100 peticiones/minuto |
| Anonimo | 20 peticiones/minuto |

Los headers de respuesta incluyen:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699999999
```

## Codigos de Error

| Codigo | Descripcion |
|--------|-------------|
| 200 | Exito |
| 201 | Recurso creado |
| 400 | Peticion invalida - revisa los parametros |
| 401 | No autenticado - se requiere login |
| 403 | Sin permisos - usuario sin autorizacion |
| 404 | No encontrado - recurso inexistente |
| 429 | Demasiadas peticiones - rate limit excedido |
| 500 | Error del servidor |

### Formato de Error

```json
{
  "code": "not_found",
  "message": "Pedido no encontrado",
  "data": {
    "status": 404
  }
}
```

---

## Grupos de Consumo

### Listar Pedidos

```bash
GET /wp-json/flavor-chat-ia/v1/pedidos
```

**Parametros:**

| Nombre | Tipo | Default | Descripcion |
|--------|------|---------|-------------|
| estado | string | abierto | abierto, cerrado, entregado, todos |
| per_page | integer | 10 | Elementos por pagina (1-100) |
| page | integer | 1 | Numero de pagina |

**Ejemplo:**

```bash
curl "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/pedidos?estado=abierto&per_page=20"
```

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "titulo": "Aceite de oliva ecologico",
      "productor": "Finca Los Olivos",
      "precio_base": 8.50,
      "precio_final": 9.35,
      "progreso": 75.5,
      "estado": "abierto",
      "cantidad_minima": 100,
      "cantidad_actual": 75.5,
      "fecha_cierre": "2024-12-15T23:59:59",
      "imagen": "https://tu-sitio.com/wp-content/uploads/aceite.jpg"
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 2
  }
}
```

### Obtener Detalle de Pedido

```bash
GET /wp-json/flavor-chat-ia/v1/pedidos/{id}
```

**Ejemplo:**

```bash
curl "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/pedidos/123"
```

### Unirse a un Pedido

```bash
POST /wp-json/flavor-chat-ia/v1/pedidos/{id}/unirse
```

**Body:**

```json
{
  "cantidad": 2.5
}
```

**Ejemplo:**

```bash
curl -X POST "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/pedidos/123/unirse" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"cantidad": 2.5}'
```

**Respuesta:**

```json
{
  "success": true,
  "message": "Te has unido al pedido!",
  "data": {
    "importe_total": 23.38
  }
}
```

### Mis Pedidos

```bash
GET /wp-json/flavor-chat-ia/v1/mis-pedidos
```

**Requiere autenticacion**

### Marcar como Pagado/Recogido

```bash
POST /wp-json/flavor-chat-ia/v1/pedidos/{id}/marcar-pagado
POST /wp-json/flavor-chat-ia/v1/pedidos/{id}/marcar-recogido
```

---

## Perfil y Preferencias

### Obtener Perfil

```bash
GET /wp-json/flavor-chat-ia/v1/gc/perfil
```

**Respuesta:**

```json
{
  "success": true,
  "data": {
    "es_miembro": true,
    "consumidor": {
      "id": 45,
      "rol": "consumidor",
      "estado": "activo",
      "preferencias_alimentarias": "Vegetariano",
      "alergias": "Frutos secos",
      "saldo_pendiente": 12.50,
      "fecha_alta": "2024-01-15"
    },
    "grupo": {
      "id": 1,
      "nombre": "Grupo de Consumo Local"
    }
  }
}
```

### Actualizar Preferencias

```bash
PUT /wp-json/flavor-chat-ia/v1/gc/preferencias
```

**Body:**

```json
{
  "preferencias_alimentarias": "Vegetariano, sin gluten",
  "alergias": "Frutos secos, marisco"
}
```

---

## Lista de Compra

### Obtener Lista

```bash
GET /wp-json/flavor-chat-ia/v1/gc/lista-compra
```

### Agregar Producto

```bash
POST /wp-json/flavor-chat-ia/v1/gc/lista-compra/agregar
```

**Body:**

```json
{
  "producto_id": 456,
  "cantidad": 2
}
```

### Eliminar de Lista

```bash
DELETE /wp-json/flavor-chat-ia/v1/gc/lista-compra/{id}
```

---

## Suscripciones

### Listar Suscripciones

```bash
GET /wp-json/flavor-chat-ia/v1/gc/suscripciones
```

### Crear Suscripcion

```bash
POST /wp-json/flavor-chat-ia/v1/gc/suscripciones
```

**Body:**

```json
{
  "tipo_cesta_id": 1,
  "frecuencia": "semanal"
}
```

### Pausar Suscripcion

```bash
POST /wp-json/flavor-chat-ia/v1/gc/suscripciones/{id}/pausar
```

### Cancelar Suscripcion

```bash
POST /wp-json/flavor-chat-ia/v1/gc/suscripciones/{id}/cancelar
```

**Body (opcional):**

```json
{
  "motivo": "Me mudo de ciudad"
}
```

---

## Catalogo de Productos

### Listar Productos

```bash
GET /wp-json/flavor-chat-ia/v1/gc/productos
```

**Parametros:**

| Nombre | Tipo | Descripcion |
|--------|------|-------------|
| categoria | string | Slug de categoria |
| productor_id | integer | ID del productor |
| busqueda | string | Texto de busqueda |
| per_page | integer | Elementos por pagina |
| page | integer | Numero de pagina |

**Ejemplo:**

```bash
curl "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/gc/productos?categoria=verduras&busqueda=tomate"
```

### Listar Productores

```bash
GET /wp-json/flavor-chat-ia/v1/gc/productores
```

**Parametros:**

| Nombre | Tipo | Descripcion |
|--------|------|-------------|
| eco | string | '1' para solo ecologicos |
| con_entrega | string | '1' para solo con entrega |

### Productores Cercanos

```bash
GET /wp-json/flavor-chat-ia/v1/gc/productores-cercanos
```

**Parametros (requeridos):**

| Nombre | Tipo | Descripcion |
|--------|------|-------------|
| lat | number | Latitud (-90 a 90) |
| lng | number | Longitud (-180 a 180) |
| limite | integer | Maximo resultados (default: 20) |

**Ejemplo:**

```bash
curl "https://tu-sitio.com/wp-json/flavor-chat-ia/v1/gc/productores-cercanos?lat=40.4168&lng=-3.7038&limite=10"
```

### Tipos de Cestas

```bash
GET /wp-json/flavor-chat-ia/v1/gc/cestas-tipo
```

### Calendario de Ciclos

```bash
GET /wp-json/flavor-chat-ia/v1/gc/ciclos/calendario?meses=3
```

### Historial de Pedidos

```bash
GET /wp-json/flavor-chat-ia/v1/gc/pedidos/historial?limite=20
```

---

## Red de Comunidades

### Directorio de Nodos

```bash
GET /wp-json/flavor-network/v1/directory
```

**Parametros:**

| Nombre | Tipo | Descripcion |
|--------|------|-------------|
| tipo | string | Tipo de entidad |
| sector | string | Sector de actividad |
| nivel | string | Nivel de consciencia |
| pais | string | Pais |
| ciudad | string | Ciudad |
| busqueda | string | Texto de busqueda |
| verificado | boolean | Solo verificados |
| pagina | integer | Numero de pagina |
| por_pagina | integer | Elementos por pagina |

### Perfil de Nodo

```bash
GET /wp-json/flavor-network/v1/node/{slug}
```

### Mapa de Nodos

```bash
GET /wp-json/flavor-network/v1/map
```

### Nodos Cercanos

```bash
GET /wp-json/flavor-network/v1/nearby?lat=40.4168&lng=-3.7038&radio=50
```

### Tablon de Anuncios

```bash
GET /wp-json/flavor-network/v1/board
POST /wp-json/flavor-network/v1/board
PUT /wp-json/flavor-network/v1/board/{id}
DELETE /wp-json/flavor-network/v1/board/{id}
```

### Eventos

```bash
GET /wp-json/flavor-network/v1/events
POST /wp-json/flavor-network/v1/events
GET /wp-json/flavor-network/v1/events/{id}
PUT /wp-json/flavor-network/v1/events/{id}
DELETE /wp-json/flavor-network/v1/events/{id}
```

### Colaboraciones

```bash
GET /wp-json/flavor-network/v1/collaborations
POST /wp-json/flavor-network/v1/collaborations
POST /wp-json/flavor-network/v1/collaborations/{id}/join
```

### Alertas Solidarias

```bash
GET /wp-json/flavor-network/v1/alerts
POST /wp-json/flavor-network/v1/alerts
```

### Ofertas de Tiempo

```bash
GET /wp-json/flavor-network/v1/time-offers
POST /wp-json/flavor-network/v1/time-offers
```

### Estadisticas de Red

```bash
GET /wp-json/flavor-network/v1/stats
```

---

## PWA

### Sincronizar Contenido

```bash
GET /wp-json/flavor/v1/pwa/sync
```

**Respuesta:**

```json
{
  "success": true,
  "urls": [
    "https://tu-sitio.com/",
    "https://tu-sitio.com/flavor-offline/",
    "https://tu-sitio.com/mi-cuenta/"
  ],
  "version": "1.0.0"
}
```

### Estado del Cache

```bash
GET /wp-json/flavor/v1/pwa/cache-status
```

**Requiere permisos de administrador**

---

## Temas

### Listar Temas

```bash
GET /wp-json/flavor/v1/themes
```

### Tema Activo

```bash
GET /wp-json/flavor/v1/themes/active
POST /wp-json/flavor/v1/themes/active
```

---

## SDKs Disponibles

### JavaScript/TypeScript

```bash
npm install @flavor-platform/api-client
```

```javascript
import { FlavorAPI } from '@flavor-platform/api-client';

const api = new FlavorAPI({
  baseUrl: 'https://tu-sitio.com',
  token: 'tu-token-jwt'
});

const pedidos = await api.gc.getPedidos({ estado: 'abierto' });
```

### PHP

```php
composer require flavor-platform/api-client

use FlavorPlatform\APIClient;

$api = new APIClient([
    'base_url' => 'https://tu-sitio.com',
    'token' => 'tu-token-jwt'
]);

$pedidos = $api->gc()->getPedidos(['estado' => 'abierto']);
```

### Flutter/Dart

```yaml
dependencies:
  flavor_platform_api: ^1.0.0
```

```dart
import 'package:flavor_platform_api/flavor_platform_api.dart';

final api = FlavorAPI(
  baseUrl: 'https://tu-sitio.com',
  token: 'tu-token-jwt',
);

final pedidos = await api.gc.getPedidos(estado: 'abierto');
```

---

## Webhooks

Flavor Platform puede enviar webhooks para eventos importantes:

### Eventos Disponibles

| Evento | Descripcion |
|--------|-------------|
| `pedido.creado` | Nuevo pedido colectivo creado |
| `pedido.cerrado` | Pedido alcanza minimo o fecha limite |
| `pedido.entregado` | Pedido marcado como entregado |
| `usuario.unido` | Usuario se une a pedido |
| `suscripcion.creada` | Nueva suscripcion |
| `suscripcion.cancelada` | Suscripcion cancelada |

### Formato del Payload

```json
{
  "event": "pedido.creado",
  "timestamp": "2024-12-01T10:30:00Z",
  "data": {
    "pedido_id": 123,
    "titulo": "Aceite ecologico"
  }
}
```

### Configurar Webhooks

Desde el panel de administracion: **Flavor Platform > Configuracion > Webhooks**

---

## Soporte

- **Documentacion**: https://docs.flavorplatform.com
- **API Status**: https://status.flavorplatform.com
- **Email**: api@flavorplatform.com
- **GitHub**: https://github.com/flavor-platform

---

## Changelog

### v1.0.0 (2024-12)
- Lanzamiento inicial de la API
- Endpoints de Grupos de Consumo
- Endpoints de Red de Comunidades
- Soporte PWA
- Autenticacion JWT
