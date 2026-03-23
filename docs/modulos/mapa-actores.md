# Módulo: Mapa de Actores

> Directorio de actores del territorio con relaciones y posiciones

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `mapa_actores` |
| **Versión** | 1.0.0+ |
| **Categoría** | Administración / Participación |

### Principios Gailu

- `transparencia` - Visibilizar estructuras de poder
- `participacion` - Conocer actores para incidir
- `cooperacion` - Identificar aliados potenciales

---

## Descripción

Directorio completo de actores del territorio: administraciones públicas, empresas, instituciones, medios de comunicación, partidos políticos, sindicatos, ONGs y colectivos. Permite mapear relaciones entre actores, registrar interacciones y visualizar el ecosistema de poder local.

### Características Principales

- **Catálogo de Actores**: Administraciones, empresas, instituciones, medios, partidos, etc.
- **Relaciones**: Mapeo de relaciones entre actores (pertenece, controla, financia, colabora...)
- **Personas Clave**: Registro de personas relevantes dentro de cada actor
- **Interacciones**: Historial de reuniones, comunicaciones, conflictos
- **Visualización**: Mapa geográfico y grafo de relaciones
- **Búsqueda**: Búsqueda fulltext por nombre, competencias y temas

---

## Tablas de Base de Datos

### `{prefix}_flavor_mapa_actores`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(255) | Nombre completo |
| `nombre_corto` | varchar(100) | Nombre abreviado |
| `descripcion` | text | Descripción |
| `tipo` | enum | Ver tipos de actor |
| `subtipo` | varchar(100) | Subtipo específico |
| `ambito` | enum | `local`, `comarcal`, `provincial`, `autonomico`, `estatal`, `internacional` |
| `posicion_general` | enum | `aliado`, `neutro`, `opositor`, `desconocido` |
| `nivel_influencia` | enum | `bajo`, `medio`, `alto`, `muy_alto` |
| `logo` | varchar(255) | URL del logo |
| `direccion` | text | Dirección física |
| `municipio` | varchar(100) | Municipio |
| `codigo_postal` | varchar(10) | Código postal |
| `telefono` | varchar(50) | Teléfono contacto |
| `email` | varchar(200) | Email contacto |
| `web` | varchar(255) | Sitio web |
| `redes_sociales` | text | JSON redes sociales |
| `responsables` | text | Responsables principales |
| `competencias` | text | Áreas de competencia |
| `temas_relacionados` | text | Temas de interés |
| `etiquetas` | text | Tags para filtrado |
| `notas_internas` | text | Notas privadas |
| `fuentes` | text | Fuentes de información |
| `verificado` | tinyint | Información verificada |
| `activo` | tinyint | Actor activo |
| `creador_id` | bigint | Usuario que lo creó |

**Índice FULLTEXT**: `nombre`, `descripcion`, `competencias`, `temas_relacionados`

### `{prefix}_flavor_mapa_actores_relaciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `actor_origen_id` | bigint | Actor de origen |
| `actor_destino_id` | bigint | Actor de destino |
| `tipo_relacion` | enum | Ver tipos de relación |
| `descripcion` | text | Descripción de la relación |
| `intensidad` | enum | `debil`, `moderada`, `fuerte` |
| `bidireccional` | tinyint | Relación bidireccional |
| `fecha_inicio` | date | Inicio de relación |
| `fecha_fin` | date | Fin de relación |
| `fuente` | varchar(255) | Fuente de información |
| `verificada` | tinyint | Relación verificada |
| `creador_id` | bigint | Usuario que la registró |

### `{prefix}_flavor_mapa_actores_interacciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `actor_id` | bigint | Actor involucrado |
| `tipo` | enum | `reunion`, `comunicacion`, `denuncia`, `colaboracion`, `conflicto`, `declaracion`, `otro` |
| `titulo` | varchar(255) | Título de la interacción |
| `descripcion` | text | Descripción detallada |
| `fecha` | date | Fecha de la interacción |
| `resultado` | enum | `positivo`, `neutro`, `negativo` |
| `documentos` | text | URLs de documentos |
| `participantes` | text | Personas que participaron |
| `campania_id` | bigint | Campaña relacionada |
| `denuncia_id` | bigint | Denuncia relacionada |
| `autor_id` | bigint | Usuario que registró |

### `{prefix}_flavor_mapa_actores_personas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `actor_id` | bigint | Actor al que pertenece |
| `nombre` | varchar(200) | Nombre completo |
| `cargo` | varchar(200) | Cargo actual |
| `departamento` | varchar(200) | Departamento |
| `email` | varchar(200) | Email de contacto |
| `telefono` | varchar(50) | Teléfono |
| `notas` | text | Notas sobre la persona |
| `fecha_desde` | date | En cargo desde |
| `fecha_hasta` | date | En cargo hasta |
| `activo` | tinyint | Actualmente activo |

---

## Tipos de Actor

| Tipo | Descripción |
|------|-------------|
| `administracion_publica` | Ayuntamientos, diputaciones, gobiernos |
| `empresa` | Empresas privadas |
| `institucion` | Instituciones diversas |
| `medio_comunicacion` | Medios de comunicación |
| `partido_politico` | Partidos políticos |
| `sindicato` | Sindicatos |
| `ong` | ONGs y fundaciones |
| `colectivo` | Colectivos y movimientos |
| `persona` | Personas individuales relevantes |
| `otro` | Otros tipos |

## Tipos de Relación

| Tipo | Descripción |
|------|-------------|
| `pertenece_a` | A pertenece a B |
| `controla` | A controla a B |
| `financia` | A financia a B |
| `colabora` | A colabora con B |
| `compite` | A compite con B |
| `influye` | A influye en B |
| `depende` | A depende de B |
| `otro` | Otra relación |

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[actores_listar]` | Listado de actores | `tipo`, `ambito`, `posicion`, `limite` |
| `[actores_detalle]` | Ficha de un actor | `id` |
| `[actores_crear]` | Formulario para crear actor | - |
| `[actores_mapa]` | Mapa geográfico de actores | - |
| `[actores_grafo]` | Grafo de relaciones | - |
| `[actores_buscar]` | Buscador de actores | - |

### Ejemplo Listado

```
[actores_listar tipo="administracion_publica" ambito="local" limite="20"]
```

### Ejemplo Detalle

```
[actores_detalle id="123"]
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `mostrar_mapa` | bool | true | Mostrar mapa geográfico |
| `mostrar_grafo_relaciones` | bool | true | Mostrar grafo de relaciones |
| `permitir_edicion_comunidad` | bool | true | Usuarios pueden editar |
| `requiere_verificacion` | bool | true | Requiere verificación admin |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `actores_crear` | Crear nuevo actor | Usuario |
| `actores_actualizar` | Actualizar actor | Usuario |
| `actores_agregar_relacion` | Añadir relación | Usuario |
| `actores_agregar_interaccion` | Registrar interacción | Usuario |
| `actores_agregar_persona` | Añadir persona clave | Usuario |
| `actores_buscar` | Buscar actores | Público |
| `actores_listar` | Listar actores | Público |
| `actores_grafo_datos` | Datos para grafo | Público |

---

## Vistas Frontend

| Vista | Archivo | Descripción |
|-------|---------|-------------|
| Listado | `views/listado.php` | Lista de actores |
| Detalle | `views/detalle.php` | Ficha completa |
| Crear | `views/crear.php` | Formulario creación |
| Mapa | `views/mapa.php` | Mapa geográfico |
| Grafo | `views/grafo.php` | Visualización relaciones |
| Buscar | `views/buscar.php` | Buscador avanzado |

---

## Dashboard Tabs

El módulo incluye vista en el panel de administración unificado.

---

## Integraciones

### Con Módulo Campañas

- Vincular actores a campañas de incidencia
- Registrar interacciones relacionadas con campañas

### Con Módulo Seguimiento Denuncias

- Vincular actores como destinatarios de denuncias
- Historial de interacciones formales

### Con Módulo Colectivos

- Actores de tipo `colectivo` pueden enlazarse
- Relaciones entre colectivos y otros actores

---

## Permisos

| Capability | Descripción |
|------------|-------------|
| Usuario logueado | Crear actores, relaciones, interacciones |
| Público | Ver listados, búsqueda, mapas |
| `manage_options` | Verificar información, administración |

---

## Notas de Implementación

- La búsqueda utiliza índice FULLTEXT de MySQL para mejor rendimiento
- El grafo de relaciones puede visualizarse con D3.js o similar
- Las posiciones (aliado/neutro/opositor) son subjetivas desde la perspectiva del colectivo
- Los niveles de influencia ayudan a priorizar interacciones
- Las personas clave permiten mantener contactos actualizados
- Las interacciones crean un historial útil para campañas
