# Módulo: Talleres

Sistema de gestión de talleres prácticos y workshops.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `talleres` |
| Versión | 1.0.0 |
| Categoría | Formación |
| Principios Gailu | Aprendizaje |

## Descripción

El módulo TALLERES permite organizar workshops prácticos con sistema de inscripciones, gestión de sesiones, tracking de asistencia, materiales descargables y emisión de certificados. Soporta talleres gratuitos y de pago con lista de espera.

## Tablas de Base de Datos

### `wp_flavor_talleres`
Talleres principales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| organizador_id | bigint | Usuario organizador |
| titulo | varchar | Nombre |
| slug | varchar | URL amigable |
| descripcion | text | Descripción completa |
| categoria | varchar | artesania, cocina, huerto, tecnologia, costura, carpinteria, etc. |
| nivel | enum | principiante, intermedio, avanzado, todos |
| duracion_horas | int | Duración total |
| numero_sesiones | int | Cantidad de sesiones |
| max_participantes | int | Máximo inscritos |
| min_participantes | int | Mínimo para confirmar |
| precio | decimal | Precio |
| es_gratuito | tinyint | Gratuito |
| estado | enum | borrador, pendiente, publicado, confirmado, en_curso, finalizado, cancelado |
| valoracion_media | decimal | Puntuación promedio |

### `wp_flavor_talleres_sesiones`
Sesiones del taller.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| taller_id | bigint | FK a taller |
| numero_sesion | int | Número |
| titulo | varchar | Título |
| fecha_hora | datetime | Fecha y hora |
| duracion_minutos | int | Duración |
| estado | enum | programada, en_curso, finalizada, cancelada |

### `wp_flavor_talleres_inscripciones`
Inscripciones de participantes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| taller_id | bigint | FK a taller |
| participante_id | bigint | Usuario |
| estado | enum | pendiente, confirmada, cancelada, completada |
| lista_espera | tinyint | En lista espera |
| sesiones_asistidas | int | Contador |
| porcentaje_asistencia | decimal | % asistencia |
| certificado_emitido | tinyint | Certificado |

### `wp_flavor_talleres_asistencias`
Registro de asistencia por sesión.

### `wp_flavor_talleres_materiales`
Recursos educativos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| taller_id | bigint | FK a taller |
| titulo | varchar | Nombre |
| tipo | enum | documento, imagen, video, enlace |
| archivo_url | varchar | URL |
| solo_inscritos | tinyint | Acceso restringido |

### `wp_flavor_talleres_valoraciones`
Reviews de participantes.

## Endpoints API REST

**Namespace:** `flavor/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/talleres` | Listar talleres |
| GET | `/talleres/{id}` | Detalle taller |
| GET | `/talleres/categorias` | Categorías |
| GET | `/talleres/calendario` | Calendario |
| GET | `/talleres/mis-inscripciones` | Mis inscripciones |
| POST | `/talleres/inscribirse` | Inscribirse |
| POST | `/talleres/cancelar` | Cancelar inscripción |
| POST | `/talleres/{id}/valorar` | Valorar taller |
| GET | `/talleres/{id}/materiales` | Descargar materiales |
| GET | `/talleres/{id}/certificado` | Descargar certificado |
| POST | `/talleres/proponer` | Proponer taller |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[proximos_talleres]` | Widget próximo taller |
| `[detalle_taller]` | Página de taller |
| `[mis_inscripciones_talleres]` | Mis inscripciones |
| `[proponer_taller]` | Formulario proponer |
| `[calendario_talleres]` | Calendario |
| `[mis_talleres_organizador]` | Panel organizador |
| `[talleres_catalogo]` | Catálogo grid |
| `[talleres_materiales]` | Descargas materiales |

## Categorías

| Categoría | Descripción |
|-----------|-------------|
| artesania | Artesanía y manualidades |
| cocina | Cocina y conservas |
| huerto | Huerto urbano y jardinería |
| tecnologia | Tecnología y digital |
| costura | Costura y textil |
| carpinteria | Carpintería básica |
| reparaciones | Reparaciones domésticas |
| reciclaje | Reciclaje creativo |
| idiomas | Idiomas |
| musica | Música |

## Estados del Taller

| Estado | Descripción |
|--------|-------------|
| borrador | En edición |
| pendiente | Esperando aprobación |
| publicado | Disponible para inscripción |
| confirmado | Mínimo alcanzado |
| en_curso | En ejecución |
| finalizado | Completado |
| cancelado | Cancelado |

## Configuración

```php
[
    'requiere_aprobacion_organizadores' => false,
    'permite_talleres_gratuitos' => true,
    'permite_talleres_pago' => true,
    'comision_talleres_pago' => 10,
    'max_participantes_por_taller' => 20,
    'min_participantes_para_confirmar' => 5,
    'permite_lista_espera' => true,
    'dias_anticipacion_cancelacion' => 2,
]
```

## Certificados

Para obtener certificado:
1. El taller debe tener `permite_certificado = true`
2. El participante debe asistir al menos al 80% de sesiones
3. El organizador o admin marca como completado

## Integraciones

El módulo acepta integraciones de:
- **Recetas**: Para talleres de cocina
- **Multimedia**: Contenido multimedia
- **Biblioteca**: Materiales de referencia

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `talleres_enviar_recordatorios` | Diaria | Recordatorios |
| `talleres_confirmar_automatico` | 2x/día | Auto-confirmar/cancelar |

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver catálogo | Público |
| Inscribirse | Usuario autenticado |
| Descargar materiales | Inscrito |
| Valorar | Taller completado |
| Proponer taller | Usuario autenticado |
| Organizar | Organizador o admin |
| Gestionar | `manage_options` |

## Dashboard Tabs Usuario

- Mis inscripciones activas
- Talleres completados
- Certificados
