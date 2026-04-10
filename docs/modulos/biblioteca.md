# Módulo: Biblioteca

Sistema de préstamo e intercambio de libros comunitarios.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `biblioteca` |
| Versión | 1.2.0 |
| Categoría | Formación |
| Principios Gailu | Aprendizaje, Economía local |
| Icono | dashicons-book |

## Descripción

El módulo BIBLIOTECA permite gestionar un catálogo de libros comunitarios con sistema de préstamos entre usuarios, reservas, reseñas y valoraciones. Incluye gamificación con puntos y múltiples tipos de transacción (donación, préstamo, intercambio).

## Tablas de Base de Datos

### `wp_flavor_biblioteca_libros`
Catálogo de libros.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| propietario_id | bigint | Usuario propietario |
| isbn | varchar(20) | ISBN |
| titulo | varchar(500) | Título |
| autor | varchar(255) | Autor |
| editorial | varchar(255) | Editorial |
| ano_publicacion | int | Año |
| idioma | varchar(50) | Idioma |
| genero | varchar(100) | Género |
| num_paginas | int | Páginas |
| descripcion | text | Descripción |
| portada_url | varchar(500) | URL portada |
| estado_fisico | enum | excelente, bueno, aceptable, desgastado |
| disponibilidad | enum | disponible, prestado, reservado, no_disponible |
| tipo | enum | donado, prestamo, intercambio |
| valoracion_media | decimal | Promedio 0-5 |
| veces_prestado | int | Contador préstamos |

### `wp_flavor_biblioteca_prestamos`
Préstamos de libros.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| libro_id | bigint | FK a libro |
| prestamista_id | bigint | Usuario propietario |
| prestatario_id | bigint | Usuario solicitante |
| fecha_solicitud | datetime | Solicitud |
| fecha_prestamo | datetime | Entrega |
| fecha_devolucion_prevista | datetime | Vencimiento |
| fecha_devolucion_real | datetime | Devolución real |
| renovaciones | int | Renovaciones |
| estado | enum | pendiente, activo, devuelto, retrasado, perdido, rechazado |
| valoracion_libro | int | Rating del libro |

### `wp_flavor_biblioteca_reservas`
Reservas de libros no disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| libro_id | bigint | FK a libro |
| usuario_id | bigint | Usuario |
| fecha_expiracion | datetime | Expiración |
| estado | enum | pendiente, confirmada, cancelada, expirada, convertida |

### `wp_flavor_biblioteca_resenas`
Reseñas de libros.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| libro_id | bigint | FK a libro |
| usuario_id | bigint | Usuario |
| valoracion | int | Puntuación 1-5 |
| resena | text | Texto reseña |

## Endpoints API REST

**Namespace:** `flavor-platform/v1` y `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/biblioteca/dashboard` | Dashboard usuario |
| GET | `/biblioteca/libros` | Listar libros |
| GET | `/biblioteca/libros/{id}` | Detalle libro |
| POST | `/biblioteca/libros` | Crear libro |
| PUT | `/biblioteca/libros/{id}` | Actualizar libro |
| DELETE | `/biblioteca/libros/{id}` | Eliminar libro |
| GET | `/biblioteca/mis-libros` | Mis libros |
| GET | `/biblioteca/mis-prestamos` | Mis préstamos |
| POST | `/biblioteca/prestamos/solicitar` | Solicitar préstamo |
| POST | `/biblioteca/prestamos/{id}/devolver` | Devolver |
| POST | `/biblioteca/prestamos/{id}/renovar` | Renovar |
| POST | `/biblioteca/reservas` | Crear reserva |
| DELETE | `/biblioteca/reservas/{id}` | Cancelar reserva |
| POST | `/biblioteca/libros/{id}/resena` | Agregar reseña |
| GET | `/biblioteca/generos` | Listar géneros |
| GET | `/biblioteca/estadisticas` | Estadísticas |
| GET | `/biblioteca/recomendaciones` | Recomendados |
| GET | `/biblioteca/isbn/{isbn}` | Buscar por ISBN |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[biblioteca_catalogo]` | Catálogo con buscador |
| `[biblioteca_detalle]` | Detalle de libro |
| `[biblioteca_mis_libros]` | Mis libros agregados |
| `[biblioteca_mis_prestamos]` | Mis préstamos |
| `[biblioteca_agregar]` | Formulario agregar libro |
| `[biblioteca_reservas]` | Mis reservas |
| `[biblioteca_busqueda]` | Buscador avanzado |
| `[biblioteca_novedades]` | Últimos libros |

## Estados del Libro

| Disponibilidad | Descripción |
|----------------|-------------|
| disponible | Listo para préstamo |
| prestado | En préstamo |
| reservado | Reservado |
| no_disponible | No disponible |

| Estado Físico | Descripción |
|---------------|-------------|
| excelente | Como nuevo |
| bueno | Buen estado |
| aceptable | Aceptable |
| desgastado | Desgastado |

## Tipos de Transacción

| Tipo | Descripción |
|------|-------------|
| donado | Libro donado a la biblioteca |
| prestamo | Préstamo temporal |
| intercambio | Intercambio entre usuarios |

## Estados del Préstamo

| Estado | Descripción |
|--------|-------------|
| pendiente | Esperando aprobación |
| activo | En préstamo |
| devuelto | Devuelto |
| retrasado | Vencido |
| perdido | Perdido |
| rechazado | Rechazado |

## Configuración

```php
[
    'permite_donaciones' => true,
    'permite_intercambios' => true,
    'permite_prestamos' => true,
    'duracion_prestamo_dias' => 30,
    'renovaciones_maximas' => 2,
    'permite_reservas' => true,
    'sistema_puntos' => true,
    'puntos_por_prestamo' => 1,
    'puntos_por_devolucion' => 2,
    'requiere_verificacion_isbn' => false,
    'notificar_vencimientos' => true,
    'dias_antes_notificar' => 3,
]
```

## Sistema de Puntos

| Acción | Puntos |
|--------|--------|
| Solicitar préstamo | +1 |
| Devolver a tiempo | +2 |
| Agregar libro | +5 |
| Escribir reseña | +3 |
| Devolver tarde | -1 |

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_biblioteca_recordatorios` | Diaria | Recordatorios vencimiento |
| `flavor_biblioteca_procesar_reservas` | Cada hora | Procesar reservas expiradas |

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver catálogo | Público |
| Ver detalles | Público |
| Solicitar préstamo | Usuario autenticado |
| Agregar libro | Usuario autenticado |
| Escribir reseña | Usuario autenticado |
| Aprobar préstamo | Propietario o admin |
| Gestionar | `manage_options` |

## Dashboard Tabs Usuario

- Mis libros agregados
- Mis préstamos activos
- Mis reservas
- Historial

## AJAX Actions

- `biblioteca_solicitar_prestamo`
- `biblioteca_devolver_libro`
- `biblioteca_renovar_prestamo`
- `biblioteca_reservar_libro`
- `biblioteca_cancelar_reserva`
- `biblioteca_valorar_libro`
- `biblioteca_agregar_libro`
- `biblioteca_buscar_isbn`
