# Módulo: Bares y Hostelería

> Directorio y gestión de bares, restaurantes y locales de hostelería

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `bares` |
| **Versión** | 1.0.0+ |
| **Categoría** | Directorio / Servicios |
| **Disponible en App** | Ambas (cliente y admin) |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`
- `Flavor_Module_Integration_Consumer`

### Integraciones Aceptadas

- `recetas` - Recetas pueden vincularse a locales
- `multimedia` - Galería de fotos del establecimiento

---

## Descripción

Directorio completo de establecimientos de hostelería con cartas/menús, reservas, valoraciones y estadísticas. Ideal para comunidades que quieren promover el comercio local.

### Características Principales

- **Directorio de Locales**: Bares, restaurantes, cafeterías, etc.
- **Cartas y Menús**: Gestión de productos por categorías
- **Reservas Online**: Sistema de reserva de mesas
- **Valoraciones**: Puntuación por usuarios
- **Eventos**: Eventos asociados a locales
- **Geolocalización**: Búsqueda por proximidad

---

## Tablas de Base de Datos

### `{prefix}_flavor_bares`

Tabla principal de establecimientos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(255) | Nombre del establecimiento |
| `tipo` | enum | Tipo de local |
| `direccion` | text | Dirección completa |
| `latitud` | decimal(10,8) | Coordenada GPS |
| `longitud` | decimal(11,8) | Coordenada GPS |
| `telefono` | varchar(20) | Teléfono de contacto |
| `email` | varchar(100) | Email de contacto |
| `web` | varchar(255) | Sitio web |
| `descripcion` | text | Descripción del local |
| `horario` | text | Horario de apertura (JSON) |
| `valoracion_media` | decimal(2,1) | Puntuación media |
| `total_valoraciones` | int | Número de valoraciones |
| `imagen_principal` | varchar(255) | URL imagen |
| `propietario_id` | bigint | Usuario propietario |
| `estado` | enum | `activo`, `inactivo` |
| `created_at` | datetime | Fecha de registro |

---

## Tipos de Establecimiento

| Tipo | Nombre |
|------|--------|
| `bar` | Bar |
| `restaurante` | Restaurante |
| `cafeteria` | Cafetería |
| `pub` | Pub |
| `terraza` | Terraza |
| `cocteleria` | Coctelería |

---

## Categorías de Carta

| Categoría | Nombre |
|-----------|--------|
| `tapas` | Tapas |
| `raciones` | Raciones |
| `entrantes` | Entrantes |
| `carnes` | Carnes |
| `pescados` | Pescados |
| `bebidas` | Bebidas |
| `postres` | Postres |
| `cocktails` | Cocktails |
| `vinos` | Vinos |
| `cafes` | Cafés |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `permitir_reservas` | bool | true | Habilitar reservas online |
| `permitir_valoraciones` | bool | true | Permitir puntuaciones |
| `requiere_login_reserva` | bool | true | Login obligatorio para reservar |
| `limite_resultados` | int | 12 | Resultados por página |

---

## REST API Endpoints

### Públicos

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor/v1/bares` | GET | Listar bares con filtros |
| `/flavor/v1/bares/{id}` | GET | Detalle de un bar |
| `/flavor/v1/bares/{id}/carta` | GET | Carta del bar |
| `/flavor/v1/bares/{id}/eventos` | GET | Eventos del bar |

### Autenticados

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor/v1/bares/{id}/reservar` | POST | Reservar mesa |

### Parámetros de Listado

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `tipo` | string | Filtrar por tipo |
| `valoracion_minima` | number | Valoración mínima |
| `busqueda` | string | Término de búsqueda |
| `latitud` | number | Lat para proximidad |
| `longitud` | number | Lng para proximidad |
| `radio_km` | number | Radio en km (default: 10) |
| `limite` | int | Resultados (default: 12) |
| `pagina` | int | Página actual |

### Parámetros de Reserva

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `nombre_reserva` | string | Sí | Nombre para la reserva |
| `telefono` | string | No | Teléfono de contacto |
| `fecha` | string | Sí | Fecha (YYYY-MM-DD) |
| `hora` | string | Sí | Hora (HH:MM) |
| `comensales` | int | No | Número de comensales |
| `notas` | string | No | Notas adicionales |

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[flavor_bares]` | Listado de bares | `tipo`, `limite` |
| `[flavor_bar]` | Detalle de un bar | `id` |
| `[flavor_bares_mapa]` | Mapa de bares | `centro`, `zoom` |
| `[flavor_bares_buscador]` | Buscador de bares | - |

---

## Dashboard Frontend

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Explorar | Buscar y explorar locales | No |
| Mis Reservas | Ver mis reservas | Sí |
| Mi Local | Gestionar mi establecimiento | Sí (propietario) |

---

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver listado | Público |
| Ver carta | Público |
| Hacer reserva | Usuario (si configurado) |
| Valorar | Usuario registrado |
| Gestionar local | Propietario del local |
| Aprobar locales | Administrador |

---

## Integración con Eventos

El módulo se integra con el módulo de eventos:

- Los locales pueden ser sede de eventos
- Relación `lugar_id` + `tipo_lugar = 'bar'`
- Muestra próximos eventos en la ficha del bar

---

## Notas de Implementación

- Usa tabla personalizada en lugar de CPT
- Búsqueda por proximidad con fórmula de Haversine
- Las valoraciones se calculan en media ponderada
- Integración con módulo de recetas y multimedia
- Rate limiting aplicado a endpoints públicos
