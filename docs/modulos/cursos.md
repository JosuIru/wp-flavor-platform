# Módulo: Cursos

Plataforma completa de formación y educación comunitaria.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `cursos` |
| Versión | 1.0.0 |
| Categoría | Formación |
| Principios Gailu | Aprendizaje |

## Descripción

El módulo CURSOS permite crear y gestionar cursos online y presenciales con sistema de matrículas, seguimiento de progreso, evaluaciones y emisión de certificados. Incluye aulas virtuales con lecciones de video, texto, quizzes y tareas.

## Tablas de Base de Datos

### `wp_flavor_cursos`
Cursos principales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| titulo | varchar | Título |
| slug | varchar | URL amigable |
| descripcion | text | Descripción |
| instructor_id | bigint | Usuario instructor |
| categoria_id | bigint | FK a categorías |
| nivel | enum | basico, intermedio, avanzado |
| duracion_horas | int | Duración total |
| estado | enum | borrador, pendiente, publicado, archivado |
| precio | decimal | Precio |
| plazas_maximas | int | Máximo alumnos |
| modalidad | enum | presencial, online, hibrido |
| certificado | tinyint | Emite certificado |

### `wp_flavor_cursos_categorias`
Categorías de cursos.

### `wp_flavor_cursos_modulos`
Secciones del curso.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| curso_id | bigint | FK a curso |
| titulo | varchar | Título |
| descripcion | text | Descripción |
| orden | int | Posición |

### `wp_flavor_cursos_lecciones`
Contenido individual.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| curso_id | bigint | FK a curso |
| modulo_id | bigint | FK a módulo |
| titulo | varchar | Título |
| contenido | longtext | Contenido |
| tipo | enum | video, texto, quiz, tarea, recurso |
| video_url | varchar | URL de video |
| duracion_minutos | int | Duración |
| orden | int | Posición |

### `wp_flavor_cursos_matriculas`
Inscripciones de estudiantes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| curso_id | bigint | FK a curso |
| usuario_id | bigint | Alumno |
| fecha_matricula | datetime | Fecha inscripción |
| estado | enum | activa, pausada, completada, cancelada |
| progreso | int | Porcentaje completado |
| certificado_emitido | tinyint | Certificado generado |

### `wp_flavor_cursos_progreso`
Seguimiento por lección.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| usuario_id | bigint | Alumno |
| leccion_id | bigint | FK a lección |
| completada | tinyint | Completada |
| puntuacion | int | Puntuación (quiz) |

### `wp_flavor_cursos_valoraciones`
Reviews de cursos.

## Endpoints API REST

**Namespace:** `flavor-chat-ia/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/cursos/dashboard` | Dashboard usuario |
| GET | `/cursos` | Listar cursos |
| GET | `/cursos/mis-cursos` | Mis cursos |
| GET | `/cursos/{id}` | Detalle curso |
| POST | `/cursos/{id}/inscribir` | Inscribirse |
| DELETE | `/cursos/{id}/cancelar` | Cancelar inscripción |
| GET | `/cursos/{id}/lecciones` | Listar lecciones |
| POST | `/cursos/lecciones/{id}/completar` | Completar lección |
| GET | `/cursos/categorias` | Categorías |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[cursos_catalogo]` | Catálogo de cursos |
| `[cursos_mis_inscripciones]` | Mis cursos |
| `[cursos_calendario]` | Calendario |
| `[cursos_destacados]` | Cursos destacados |
| `[cursos_busqueda]` | Buscador |
| `[cursos_aula]` | Aula virtual |
| `[cursos_mi_progreso]` | Mi progreso |
| `[cursos_proximos]` | Próximos cursos |

## Tipos de Lección

| Tipo | Descripción |
|------|-------------|
| video | Video embebido |
| texto | Contenido texto |
| quiz | Cuestionario |
| tarea | Tarea a entregar |
| recurso | Recurso descargable |

## Modalidades

| Modalidad | Descripción |
|-----------|-------------|
| presencial | En persona |
| online | Virtual |
| hibrido | Combinado |

## Configuración

```php
[
    'nombre_academia' => 'Nombre del centro',
    'permitir_inscripcion_publica' => true,
    'requiere_aprobacion' => false,
    'enviar_certificados' => true,
    'duracion_acceso_dias' => 365,
    'permitir_comentarios' => true,
    'mostrar_progreso' => true,
    'habilitar_examenes' => true,
    'puntuacion_minima_aprobar' => 70,
    'intentos_examen' => 3,
    'video_provider' => 'youtube',
    'categorias' => [
        'tecnologia', 'idiomas', 'cocina', 'artesania',
        'jardineria', 'reparaciones', 'musica', 'salud'
    ],
]
```

## Integraciones

El módulo acepta integraciones de:
- **Multimedia**: Galería de contenido
- **Biblioteca**: Recursos documentales
- **Podcast**: Contenido de audio
- **Encuestas**: Evaluaciones

## Hooks

### Acciones
```php
do_action('flavor_leccion_completada', $usuario_id, $leccion_id, $curso_id);
do_action('flavor_curso_completado', $usuario_id, $curso_id);
```

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `cursos_enviar_recordatorios` | Diaria | Recordatorios a estudiantes |

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver catálogo | Público |
| Ver detalles | Público |
| Inscribirse | Usuario autenticado |
| Acceder aula | Alumno inscrito |
| Completar lección | Alumno inscrito |
| Crear curso | Instructor o admin |
| Gestionar | `manage_options` |

## Dashboard Tabs Usuario

- Mis cursos activos
- Cursos completados
- Certificados
