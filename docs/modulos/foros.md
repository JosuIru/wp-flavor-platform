# Módulo: Foros

Sistema completo de foros de discusión comunitarios.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `foros` |
| Versión | 1.0.0 |
| Categoría | Comunicación |
| Principios Gailu | Gobernanza, Aprendizaje |

## Descripción

El módulo FOROS permite crear espacios de debate y discusión organizados por categorías. Los usuarios pueden crear temas, responder, votar respuestas y marcar soluciones. Incluye sistema de menciones, seguimiento de temas y notificaciones.

## Tablas de Base de Datos

### `wp_flavor_foros`
Categorías/foros principales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | varchar(200) | Nombre del foro |
| descripcion | text | Descripción |
| icono | varchar(100) | Icono dashicon |
| orden | int | Orden de visualización |
| estado | enum | activo, cerrado, archivado |
| moderadores | text | JSON con IDs |

### `wp_flavor_foros_hilos`
Temas de discusión.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| foro_id | bigint | FK a foros |
| autor_id | bigint | Usuario autor |
| titulo | varchar(255) | Título del tema |
| contenido | longtext | Contenido |
| estado | enum | abierto, cerrado, fijado, eliminado |
| vistas | int | Contador de vistas |
| respuestas_count | int | Total de respuestas |

### `wp_flavor_foros_respuestas`
Respuestas a temas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| hilo_id | bigint | FK a hilos |
| autor_id | bigint | Usuario autor |
| contenido | longtext | Contenido |
| parent_id | bigint | Respuesta padre (anidamiento) |
| es_solucion | tinyint | Marcada como solución |
| votos | int | Votos netos |

### `wp_flavor_foros_votos`
Registro de votos en respuestas.

## Endpoints API REST

**Namespace:** `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/foros` | Listar foros |
| GET | `/foros/{id}/temas` | Temas de un foro |
| GET | `/foros/temas/{id}` | Detalle de tema |
| POST | `/foros/temas` | Crear tema |
| POST | `/foros/temas/{id}/responder` | Responder |
| GET | `/foros/buscar` | Buscar en foros |
| GET | `/foros/mis-temas` | Mis temas |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_foros_listado]` | Lista de foros |
| `[flavor_foros_categoria]` | Temas de una categoría |
| `[flavor_foros_tema]` | Tema con respuestas |
| `[flavor_foros_nuevo_tema]` | Formulario crear tema |
| `[flavor_foros_mis_temas]` | Mis temas |
| `[flavor_foros_buscar]` | Buscador |
| `[flavor_foros_actividad_reciente]` | Actividad reciente |

## Configuración

```php
[
    'permitir_registro' => true,
    'requiere_aprobacion_post' => false,
    'permitir_anonimos' => false,
    'permitir_adjuntos' => true,
    'max_tamano_adjunto' => 5, // MB
    'posts_por_pagina' => 20,
    'permitir_editar' => true,
    'minutos_edicion' => 30,
    'habilitar_reputacion' => true,
    'puntos_nuevo_tema' => 5,
    'puntos_respuesta' => 2,
    'puntos_mejor_respuesta' => 10,
    'habilitar_mencion' => true,
]
```

## Integraciones

El módulo FOROS provee tabs de discusión a:
- Grupos de Consumo
- Eventos
- Comunidades
- Incidencias
- Presupuestos Participativos
- Transparencia
- Avisos Municipales

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver foros | Público |
| Crear tema | Usuario autenticado |
| Responder | Usuario autenticado |
| Votar | Usuario autenticado |
| Marcar solución | Autor del tema |
| Moderar | `manage_options` |

## Hooks

### Acciones
```php
do_action('flavor_foros_contenido_reportado', $tipo, $id, $usuario_id, $motivo);
do_action('flavor_foros_respuesta_creada', $respuesta_id, $datos);
```

### Filtros
```php
apply_filters('flavor_foros_mapeo_entidad_foro', $id_foro, $entidad, $entidad_id);
```
