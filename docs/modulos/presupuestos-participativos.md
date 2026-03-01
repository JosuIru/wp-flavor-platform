# Módulo: Presupuestos Participativos

> Sistema de presupuestos participativos ciudadanos

## Descripción

Plataforma completa para gestionar procesos de presupuestos participativos donde los ciudadanos proponen proyectos, debaten y votan para decidir en qué se invierte una parte del presupuesto público.

## Archivos Principales

```
includes/modules/presupuestos-participativos/
├── class-presupuestos-participativos-module.php
├── class-presupuestos-participativos-dashboard-tab.php
├── install.php
├── views/
│   ├── dashboard.php
│   ├── formulario-propuesta.php
│   ├── interfaz-votacion.php
│   ├── listado-proyectos.php
│   ├── mis-propuestas.php
│   ├── presupuesto.php
│   ├── proyectos.php
│   ├── resultados.php
│   └── votos.php
└── assets/
```

## Tablas de Base de Datos

### wp_flavor_pp_ediciones
Ediciones/ciclos de presupuestos participativos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| nombre | varchar(255) | Nombre edición |
| slug | varchar(255) UNIQUE | Identificador |
| anio | year | Año fiscal |
| descripcion | text | Descripción |
| presupuesto_total | decimal(15,2) | Presupuesto disponible |
| presupuesto_ejecutado | decimal(15,2) | Ya ejecutado |
| fase_actual | enum | preparacion/propuestas/valoracion/votacion/ejecucion/finalizado |
| estado | enum | borrador/activa/cerrada/archivada |
| fecha_inicio | date | Inicio proceso |
| fecha_fin | date | Fin proceso |
| fecha_propuestas_inicio | datetime | Inicio fase propuestas |
| fecha_propuestas_fin | datetime | Fin fase propuestas |
| fecha_votacion_inicio | datetime | Inicio votación |
| fecha_votacion_fin | datetime | Fin votación |
| votos_por_ciudadano | int | Nº votos permitidos |
| presupuesto_max_proyecto | decimal(15,2) | Máximo por proyecto |
| requiere_verificacion | tinyint(1) | Verificar votantes |
| ambito | varchar(100) | Ciudad/distrito/barrio |
| imagen | varchar(500) | Imagen edición |
| bases_url | varchar(500) | URL bases legales |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha creación |

**Índices:** slug, anio, fase_actual, estado

### wp_flavor_pp_categorias
Categorías de proyectos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| edicion_id | bigint(20) | FK edición (0=global) |
| nombre | varchar(100) | Nombre |
| slug | varchar(100) | Identificador |
| descripcion | text | Descripción |
| icono | varchar(50) | Icono |
| color | varchar(7) | Color |
| presupuesto_asignado | decimal(15,2) | Presupuesto categoría |
| orden | int | Orden listado |
| activa | tinyint(1) | Está activa |

### wp_flavor_pp_propuestas
Propuestas de proyectos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| edicion_id | bigint(20) | FK edición |
| proceso_id | bigint(20) | FK proceso (si aplica) |
| usuario_id | bigint(20) | FK proponente |
| titulo | varchar(255) | Título |
| slug | varchar(255) | URL amigable |
| descripcion | text | Descripción corta |
| justificacion | text | Justificación necesidad |
| beneficiarios | text | A quién beneficia |
| contenido | longtext | Descripción completa |
| ubicacion_texto | varchar(255) | Ubicación textual |
| ubicacion_latitud | decimal(10,8) | Coordenada |
| ubicacion_longitud | decimal(11,8) | Coordenada |
| ambito | varchar(100) | Ámbito territorial |
| categoria_id | bigint(20) | FK categoría |
| presupuesto_estimado | decimal(15,2) | Coste estimado |
| presupuesto_validado | decimal(15,2) | Coste tras valoración |
| imagen | varchar(500) | Imagen principal |
| galeria | longtext JSON | Galería imágenes |
| documentos | longtext JSON | Documentos adjuntos |
| estado | enum | borrador/enviada/admitida/rechazada/valoracion/votacion/seleccionada/ejecucion/ejecutada/descartada |
| viabilidad_tecnica | enum | pendiente/viable/no_viable/con_modificaciones |
| viabilidad_economica | enum | pendiente/viable/no_viable/con_modificaciones |
| informe_viabilidad | text | Informe técnico |
| revisor_id | bigint(20) | FK técnico revisor |
| fecha_revision | datetime | Fecha revisión |
| motivo_rechazo | text | Si rechazada |
| votos_total | int | Votos recibidos |
| votos_positivos | int | Votos a favor |
| votos_negativos | int | Votos en contra |
| posicion_ranking | int | Posición final |
| apoyos_count | int | Apoyos previos |
| comentarios_count | int | Comentarios |
| visualizaciones | int | Vistas |
| destacada | tinyint(1) | Es destacada |
| metadata | longtext JSON | Datos adicionales |
| created_at | datetime | Fecha creación |
| updated_at | datetime | Última actualización |

**Índices:** edicion_id, usuario_id, categoria_id, estado, votos_total

### wp_flavor_pp_votos
Votos de ciudadanos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| edicion_id | bigint(20) | FK edición |
| proceso_id | bigint(20) | FK proceso |
| propuesta_id | bigint(20) | FK propuesta |
| usuario_id | bigint(20) | FK votante |
| tipo_voto | enum | positivo/negativo/abstencion |
| prioridad | tinyint | Orden prioridad (1=más importante) |
| peso | decimal(3,2) | Peso del voto |
| verificado | tinyint(1) | Votante verificado |
| metodo_verificacion | varchar(50) | Cómo se verificó |
| ip_hash | varchar(64) | Hash IP |
| fecha_voto | datetime | Fecha voto |

**Índices:** edicion_id, propuesta_id, usuario_id
**Unique:** edicion_id + propuesta_id + usuario_id

### wp_flavor_pp_apoyos
Apoyos en fase de propuestas (antes de votación).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| propuesta_id | bigint(20) | FK propuesta |
| usuario_id | bigint(20) | FK usuario |
| comentario | text | Comentario de apoyo |
| created_at | datetime | Fecha apoyo |

**Unique:** propuesta_id + usuario_id

### wp_flavor_pp_comentarios
Comentarios y debate en propuestas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| propuesta_id | bigint(20) | FK propuesta |
| padre_id | bigint(20) | FK comentario padre |
| usuario_id | bigint(20) | FK usuario |
| usuario_nombre | varchar(100) | Nombre visible |
| contenido | text | Contenido |
| tipo | enum | comentario/pregunta/sugerencia/oficial |
| likes_count | int | Me gusta |
| es_oficial | tinyint(1) | Respuesta oficial |
| es_destacado | tinyint(1) | Fijado arriba |
| estado | enum | publicado/moderado/eliminado |
| created_at | datetime | Fecha |

### wp_flavor_pp_ejecucion
Seguimiento de ejecución de proyectos ganadores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| propuesta_id | bigint(20) UNIQUE | FK propuesta |
| presupuesto_asignado | decimal(15,2) | Presupuesto final |
| presupuesto_ejecutado | decimal(15,2) | Gastado hasta ahora |
| estado | enum | pendiente/licitacion/adjudicado/en_obra/finalizado/cancelado |
| fecha_inicio_prevista | date | Inicio previsto |
| fecha_fin_prevista | date | Fin previsto |
| fecha_inicio_real | date | Inicio real |
| fecha_fin_real | date | Fin real |
| responsable_id | bigint(20) | FK técnico responsable |
| departamento | varchar(100) | Departamento |
| empresa_adjudicataria | varchar(255) | Empresa ejecutora |
| contrato_referencia | varchar(100) | Nº contrato |
| porcentaje_avance | tinyint | % completado |
| ultima_actualizacion | datetime | Último update |
| notas_internas | text | Notas técnicas |
| metadata | longtext JSON | Datos adicionales |

### wp_flavor_pp_actualizaciones
Actualizaciones públicas de ejecución.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint(20) | ID único |
| ejecucion_id | bigint(20) | FK ejecución |
| titulo | varchar(255) | Título update |
| contenido | text | Descripción |
| tipo | enum | avance/hito/incidencia/finalizacion |
| porcentaje | tinyint | % en este punto |
| imagenes | longtext JSON | Fotos de obra |
| documentos | longtext JSON | Documentos |
| publicado_por | bigint(20) | FK usuario |
| es_publica | tinyint(1) | Visible ciudadanos |
| created_at | datetime | Fecha |

## Shortcodes

### Procesos y Propuestas

```php
[pp_proyectos]
// Lista de proyectos/propuestas
// - edicion: ID o 'actual'
// - categoria: slug
// - estado: admitida|votacion|seleccionada|ejecucion
// - orden: votos|recientes|presupuesto
// - limite: número

[pp_proponer]
// Formulario nueva propuesta
// - edicion: ID o 'actual'
// - categorias: slugs permitidas

[pp_mis_propuestas]
// Propuestas del usuario
// - edicion: ID o 'todas'
```

### Votación

```php
[pp_votar]
// Interfaz de votación
// - edicion: ID o 'actual'
// - modo: lista|mapa|ranking

[pp_resultados]
// Resultados de votación
// - edicion: ID o 'actual'
// - mostrar_votos: true|false
// - mostrar_ranking: true|false
```

### Información

```php
[pp_presupuesto]
// Estado del presupuesto
// - edicion: ID o 'actual'
// - mostrar_ejecutado: true|false
// - grafico: circular|barras

[pp_calendario]
// Calendario del proceso
// - edicion: ID o 'actual'

[pp_estadisticas]
// Estadísticas generales
// - edicion: ID o 'actual'

[pp_ediciones]
// Histórico de ediciones
// - limite: número
```

## Dashboard Tab

**Clase:** `Flavor_Presupuestos_Participativos_Dashboard_Tab`

**Tabs disponibles:**
- `dashboard` - Proceso actual
- `proyectos` - Lista proyectos
- `proponer` - Nueva propuesta
- `votar` - Votación (si activa)
- `mis-propuestas` - Mis propuestas
- `resultados` - Resultados
- `seguimiento` - Ejecución
- `ediciones` - Histórico

## Páginas Dinámicas

| Ruta | Acción | Descripción |
|------|--------|-------------|
| `/mi-portal/presupuestos-participativos/` | index | Dashboard |
| `/mi-portal/presupuestos-participativos/proyectos/` | proyectos | Lista |
| `/mi-portal/presupuestos-participativos/proponer/` | proponer | Nueva |
| `/mi-portal/presupuestos-participativos/votar/` | votar | Votación |
| `/mi-portal/presupuestos-participativos/mis-propuestas/` | mis-propuestas | Propias |
| `/mi-portal/presupuestos-participativos/resultados/` | resultados | Resultados |
| `/mi-portal/presupuestos-participativos/presupuesto/` | presupuesto | Estado |
| `/mi-portal/presupuestos-participativos/proyecto/{id}/` | ver | Detalle |
| `/mi-portal/presupuestos-participativos/ediciones/` | ediciones | Histórico |
| `/mi-portal/presupuestos-participativos/seguimiento/` | seguimiento | Ejecución |

## Vinculaciones con Otros Módulos

| Módulo | Tipo | Descripción |
|--------|------|-------------|
| participacion | Complemento | Otros procesos participativos |
| transparencia | Datos | Publicación resultados |
| comunidades | Ámbito | PP por comunidad |
| encuestas | Consulta | Consultas previas |
| incidencias | Origen | Propuestas desde incidencias |

## Fases del Proceso

1. **Preparación**: Configuración de la edición
2. **Propuestas**: Ciudadanos envían propuestas
3. **Valoración**: Técnicos valoran viabilidad
4. **Votación**: Ciudadanos votan
5. **Selección**: Se seleccionan ganadores
6. **Ejecución**: Seguimiento de obras
7. **Finalización**: Cierre y evaluación

## Hooks y Filtros

### Actions

```php
// Propuesta enviada
do_action('flavor_pp_propuesta_enviada', $propuesta_id, $usuario_id);

// Propuesta admitida
do_action('flavor_pp_propuesta_admitida', $propuesta_id);

// Voto registrado
do_action('flavor_pp_voto_registrado', $propuesta_id, $usuario_id, $tipo_voto);

// Votación cerrada
do_action('flavor_pp_votacion_cerrada', $edicion_id);

// Proyecto seleccionado
do_action('flavor_pp_proyecto_seleccionado', $propuesta_id, $presupuesto);

// Actualización de ejecución
do_action('flavor_pp_ejecucion_actualizada', $ejecucion_id, $porcentaje);

// Proyecto finalizado
do_action('flavor_pp_proyecto_finalizado', $propuesta_id);
```

### Filters

```php
// Votos por ciudadano
apply_filters('flavor_pp_votos_permitidos', $cantidad, $edicion_id, $usuario_id);

// Validar propuesta
apply_filters('flavor_pp_validar_propuesta', $valido, $datos);

// Presupuesto máximo propuesta
apply_filters('flavor_pp_presupuesto_maximo', $maximo, $edicion_id, $categoria_id);

// Campos formulario propuesta
apply_filters('flavor_pp_campos_propuesta', $campos, $edicion_id);
```

## Configuración

```php
'presupuestos_participativos' => [
    'enabled' => true,
    'votos_por_ciudadano' => 5,
    'permitir_voto_negativo' => false,
    'apoyos_minimos_admision' => 10,
    'requiere_verificacion_voto' => true,
    'metodos_verificacion' => ['censo', 'dni', 'email'],
    'mostrar_votos_parciales' => false,
    'permitir_comentarios' => true,
    'moderacion_comentarios' => true,
    'notificaciones' => [
        'propuesta_enviada' => true,
        'propuesta_admitida' => true,
        'votacion_abierta' => true,
        'resultado_publicado' => true,
        'actualizacion_ejecucion' => true,
    ],
]
```

## Verificación de Votantes

Métodos disponibles:
- **Censo**: Verificación contra padrón
- **DNI/NIF**: Validación documento
- **Email**: Confirmación email
- **SMS**: Código por SMS
- **Presencial**: Verificación en oficina

## Permisos y Capabilities

| Capability | Descripción |
|------------|-------------|
| `pp_ver_proceso` | Ver información pública |
| `pp_proponer` | Enviar propuestas |
| `pp_votar` | Participar en votación |
| `pp_valorar` | Valorar viabilidad (técnico) |
| `pp_gestionar` | Administrar proceso |
| `pp_actualizar_ejecucion` | Publicar actualizaciones |
