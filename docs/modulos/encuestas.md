# Módulo: Encuestas

Sistema centralizado de encuestas, formularios y quizzes.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `encuestas` |
| Versión | 1.0.0 |
| Categoría | Comunicación |
| Icono | dashicons-forms |
| Color | #8b5cf6 |

## Descripción

El módulo ENCUESTAS permite crear encuestas, formularios y quizzes reutilizables que pueden integrarse en múltiples contextos: foros, comunidades, eventos, cursos, etc. Soporta 15 tipos de campos diferentes y múltiples configuraciones de visualización.

## Tablas de Base de Datos

### `wp_flavor_encuestas`
Encuestas principales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | ID único |
| titulo | varchar | Título |
| descripcion | longtext | Instrucciones |
| autor_id | bigint | Creador |
| estado | varchar | borrador, activa, cerrada, archivada |
| tipo | varchar | encuesta, formulario, quiz |
| contexto_tipo | varchar | general, chat_grupo, foro, comunidad, evento, curso |
| contexto_id | bigint | ID de entidad vinculada |
| es_anonima | tinyint | Encuesta anónima |
| permite_multiples | tinyint | Múltiples respuestas |
| mostrar_resultados | varchar | siempre, al_votar, al_cerrar, nunca |
| fecha_cierre | datetime | Cierre automático |

### `wp_flavor_encuestas_campos`
Campos/preguntas de cada encuesta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | ID único |
| encuesta_id | bigint | FK a encuestas |
| tipo | varchar | Tipo de campo |
| etiqueta | varchar | Texto de la pregunta |
| opciones | json | Opciones de selección |
| es_requerido | tinyint | Campo obligatorio |
| orden | int | Posición |

### `wp_flavor_encuestas_respuestas`
Respuestas individuales.

### `wp_flavor_encuestas_participantes`
Registro de participantes.

## Tipos de Campos

| Tipo | Descripción |
|------|-------------|
| texto | Texto corto |
| textarea | Texto largo |
| email | Email |
| telefono | Teléfono |
| url | URL |
| seleccion_unica | Radio buttons |
| seleccion_multiple | Checkboxes |
| fecha | Selector de fecha |
| fecha_hora | Fecha y hora |
| numero | Campo numérico |
| rango | Slider |
| escala | Escala 1-10 |
| nps | Net Promoter Score |
| si_no | Sí/No |
| estrellas | Rating 1-5 |

## Endpoints API REST

**Namespace:** `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/encuestas` | Crear encuesta |
| GET | `/encuestas` | Listar encuestas |
| GET | `/encuestas/{id}` | Obtener encuesta |
| PUT | `/encuestas/{id}` | Actualizar |
| DELETE | `/encuestas/{id}` | Eliminar |
| POST | `/encuestas/{id}/campos` | Agregar campo |
| POST | `/encuestas/{id}/responder` | Responder |
| GET | `/encuestas/{id}/resultados` | Ver resultados |
| POST | `/encuestas/{id}/cerrar` | Cerrar encuesta |
| GET | `/encuestas/contexto/{tipo}/{id}` | Por contexto |

## Shortcodes

| Shortcode | Atributos | Descripción |
|-----------|-----------|-------------|
| `[flavor_encuesta]` | id | Muestra encuesta |
| `[flavor_encuesta_crear]` | contexto, contexto_id | Formulario crear |
| `[flavor_encuestas_contexto]` | tipo, id, estado, limit | Lista por contexto |
| `[flavor_encuesta_resultados]` | id, formato | Solo resultados |
| `[flavor_encuesta_mini]` | id | Versión compacta |

## Configuración

```php
[
    'permitir_encuestas_anonimas' => true,
    'permitir_multiples_respuestas' => false,
    'moderacion_encuestas' => true,
    'max_opciones_por_pregunta' => 10,
    'max_campos_por_encuesta' => 20,
    'duracion_default_dias' => 7,
    'notificar_nuevas_respuestas' => true,
    'notificar_cierre_encuesta' => true,
    'permitir_exportar_resultados' => true,
]
```

## Contextos Soportados

- `general` - Sin contexto específico
- `chat_grupo` - Grupos de chat
- `foro` - Foros de discusión
- `comunidad` - Comunidades
- `evento` - Eventos
- `curso` - Cursos
- `red_social` - Red social

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver encuesta activa | Público |
| Responder | Público (configurable) |
| Crear encuesta | Usuario autenticado |
| Editar/eliminar | Autor o admin |
| Ver resultados | Según configuración |

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_encuestas_cerrar_expiradas` | Cada hora | Cierra encuestas expiradas |

## Hooks

### Acciones
```php
do_action('flavor_encuesta_creada', $encuesta_id, $datos);
do_action('flavor_encuesta_respondida', $encuesta_id, $usuario_id, $respuestas);
```
