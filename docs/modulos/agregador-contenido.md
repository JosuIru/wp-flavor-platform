# Módulo: Agregador de Contenido

> Importa noticias de fuentes RSS externas y gestiona videos de YouTube

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `agregador_contenido` |
| **Versión** | 3.5.0+ |
| **Categoría** | Contenido / Comunicación |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`

### Principios Gailu

- **Implementa**: `aprendizaje`, `gobernanza`
- **Contribuye a**: `cohesion`, `impacto`

---

## Descripción

Módulo para agregar contenido externo relevante para la comunidad. Importa noticias de fuentes RSS con filtrado por palabras clave y gestiona videos de YouTube relacionados.

### Características Principales

- **Fuentes RSS**: Configurar y gestionar feeds
- **Importación Automática**: Cron cada hora
- **Filtrado**: Por palabras clave de la comunidad
- **Videos YouTube**: Añadir videos individuales o playlists
- **Categorización**: Taxonomía compartida
- **Feed Combinado**: Mezclar noticias y videos

---

## Custom Post Types

### flavor_rss_fuente

Fuentes RSS configuradas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `title` | text | Nombre de la fuente |
| `_feed_url` | meta | URL del feed RSS |
| `_keywords` | meta | Palabras clave filtro |
| `_last_import` | meta | Última importación |

### flavor_noticia

Noticias importadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `title` | text | Título de la noticia |
| `content` | editor | Contenido |
| `_source_name` | meta | Nombre de la fuente |
| `_source_url` | meta | URL original |

**Supports**: title, editor, thumbnail, excerpt, custom-fields
**Archive**: true (slug: `noticias-comunidad`)
**REST API**: true

### flavor_video_yt

Videos de YouTube.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `title` | text | Título del video |
| `_video_id` | meta | ID de YouTube |
| `_video_url` | meta | URL completa |
| `_channel_name` | meta | Nombre del canal |
| `_duration` | meta | Duración |
| `_thumbnail_url` | meta | URL thumbnail |

**Supports**: title, editor, thumbnail, custom-fields
**Archive**: true (slug: `videos-comunidad`)
**REST API**: true

---

## Taxonomía

### flavor_contenido_cat

Categorías compartidas para noticias y videos.

- **Aplicable a**: `flavor_noticia`, `flavor_video_yt`
- **Jerárquica**: Sí
- **Slug**: `contenido-categoria`

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[agregador_noticias]` | Grid de noticias | `limit`, `categoria` |
| `[agregador_videos]` | Grid de videos | `limit`, `categoria` |
| `[agregador_feed_combinado]` | Feed mixto | `limit` |
| `[agregador_carrusel_videos]` | Carrusel de videos | `limit` |

---

## Acciones del Módulo

| Acción | Descripción |
|--------|-------------|
| `listar_noticias` | Listar noticias importadas |
| `listar_videos` | Listar videos de YouTube |
| `importar_feed` | Importar artículos de un feed |
| `agregar_video` | Añadir un video de YouTube |
| `listar_fuentes` | Listar fuentes RSS configuradas |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `flavor_import_single_feed` | Importar un feed específico | Admin |
| `flavor_add_youtube_video` | Añadir video por URL | Admin |
| `flavor_import_youtube_playlist` | Importar playlist | Admin |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_agregador_import_rss` | Cada hora | Importar todos los feeds |

---

## REST API Endpoints

El módulo registra endpoints para integración.

---

## Tool Definitions (Claude)

El módulo proporciona tools para el chat IA:

| Tool | Descripción |
|------|-------------|
| `agregador_listar_noticias` | Lista noticias importadas |
| `agregador_listar_videos` | Lista videos guardados |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Agregador | `flavor-agregador` | Panel principal |

---

## Flujo de Trabajo

```
1. CONFIGURAR FUENTES
   Añadir feeds RSS con URL y keywords
   ↓
2. IMPORTACIÓN AUTOMÁTICA
   Cron cada hora procesa feeds
   Filtra por palabras clave
   ↓
3. NOTICIAS CREADAS
   Se crean posts tipo flavor_noticia
   Con metadatos de fuente
   ↓
4. AÑADIR VIDEOS
   Manual: URL individual
   Automático: Importar playlist
   ↓
5. CATEGORIZAR
   Asignar categorías compartidas
   ↓
6. MOSTRAR
   Shortcodes en frontend
   Feed combinado o separado
```

---

## Añadir Video desde URL

El método `add_video_from_url($url)`:

1. Extrae ID de YouTube de la URL
2. Verifica si ya existe (evita duplicados)
3. Obtiene info via oEmbed
4. Crea post `flavor_video_yt`
5. Guarda thumbnail como imagen destacada

---

## Knowledge Base (IA)

El módulo proporciona contexto al chat:

> "El módulo Agregador de Contenido permite importar noticias de fuentes RSS externas filtradas por palabras clave relacionadas con la comunidad, y gestionar videos de YouTube relevantes."

---

## Notas de Implementación

- Las fuentes RSS usan CPT privado (no público)
- Las noticias y videos son públicos con archive
- Filtrado por keywords evita spam irrelevante
- oEmbed de YouTube para obtener metadatos
- Thumbnail se descarga y guarda localmente
- Categorías compartidas facilitan la organización
- Compatible con REST API para apps
