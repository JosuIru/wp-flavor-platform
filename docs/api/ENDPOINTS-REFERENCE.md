# Referencia Completa de Endpoints

Tabla exhaustiva de todos los endpoints REST disponibles en Flavor Chat IA.

## Autenticación

Todos los endpoints requieren autenticación:
- **Header:** `X-VBP-Key: <tu-api-key>`
- **Parámetro:** `?api_key=<tu-api-key>`

---

## Site Builder API

**Base:** `https://tu-sitio.com/wp-json/flavor-site-builder/v1`

### Información

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/profiles` | - | `{ profile_id: { id, name, description, icon, modules, color } }` | Lista perfiles de aplicación |
| GET | `/templates` | - | `{ template_id: { id, name, description, icon, modules, pages, has_landing, has_demo } }` | Lista plantillas disponibles |
| GET | `/themes` | - | `{ theme_id: { name, style, colors: { primary, secondary, background, surface, text } } }` | Lista temas de diseño |
| GET | `/modules` | - | `{ modules: [], categories: [] }` | Lista todos los módulos |
| GET | `/config-template/{template}` | `template`: string | `{ template_id, name, modules, pages, menu, landing, demo, _create_command }` | Config completa de plantilla |
| GET | `/design/options` | - | `{ colors: [], fonts: [], styles: [] }` | Opciones de diseño |
| GET | `/animations` | - | `{ animations: [], effects: [] }` | Animaciones disponibles |
| GET | `/tools` | - | `{ tools: [] }` | Herramientas del sistema |
| GET | `/site/status` | - | `{ site_name, profile, theme, modules, pages, menus, layout, demo_data }` | Estado del sitio |

### Creación

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| POST | `/site/create` | `template`, `site_name`, `theme?`, `import_demo?`, `create_menu?`, `create_pages?`, `modules?`, `customization?` | `{ success, steps: { profile, modules, pages, menu, theme, demo_data }, site_url }` | **Crear sitio completo** |
| POST | `/analyze` | `requirements`: string | `{ suggested_template, modules, pages, theme }` | Analizar requisitos con IA |
| POST | `/pages/create-for-modules` | `modules`: array, `publish?`: bool | `{ created: [{ id, title, url }] }` | Crear páginas para módulos |
| POST | `/menu` | `name`, `items`: array, `location?` | `{ menu_id, items_created }` | Crear menú |
| POST | `/modules/activate` | `modules`: array | `{ activated: [], failed: [] }` | Activar módulos |
| POST | `/profile/set` | `profile`: string | `{ success, profile }` | Establecer perfil |
| POST | `/theme/apply` | `theme`, `customization?` | `{ applied, colors }` | Aplicar tema |
| POST | `/demo-data/import` | `modules?`: array, `count?`: object | `{ imported: { module: { type: count } } }` | Importar datos demo |

### Validación y Exportación

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| POST | `/site/validate` | `template?`, `theme?`, `modules?`, `settings?` | `{ valid, errors, warnings, config }` | Validar config antes de aplicar |
| GET | `/site/export` | `include_content?`: bool | `{ version, exported, profile, theme, modules, design, menus, pages? }` | Exportar configuración |
| POST | `/site/import` | Config JSON, `dry_run?`: bool | `{ success, dry_run, results: { profile, theme, modules, design } }` | Importar configuración |
| GET | `/system/health` | - | `{ status, timestamp, version, php, wp, checks: { database, modules, vbp, options } }` | Health check del sistema |

---

## Site Config API

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

### Layouts

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/site/layouts` | - | `{ active, available_menus, available_footers, presets, current_settings, menu_details, footer_details }` | Layouts disponibles |
| POST | `/site/layouts/active` | `menu?`, `footer?`, `preset?` | `{ success, active_menu, active_footer }` | Configurar layout activo |
| POST | `/site/layouts/settings` | Ver campos abajo | `{ success, settings }` | Actualizar ajustes |

**Campos de `/site/layouts/settings`:**
- `cta_text`, `cta_url`, `cta_style`
- `logo_url`, `logo_dark_url`, `logo_width`
- `social_links`: object
- `contact_phone`, `contact_email`, `contact_address`
- `business_hours`, `copyright_text`
- `app_store_url`, `play_store_url`
- `header_bg_color`, `header_text_color`
- `footer_bg_color`, `footer_text_color`
- `sponsors`: array

### Menús WordPress

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/site/menus` | - | `{ menus: [{ id, name, slug, items_count, items }], locations, registered_locations }` | Listar menús |
| POST | `/site/menus` | `name`, `items`: array, `location?` | `{ success, menu_id, items_created, location }` | Crear menú |
| PUT | `/site/menus/{id}` | `items?`: array, `name?` | `{ success, menu_id, items_updated }` | Actualizar menú |
| POST | `/site/menus/locations` | `menu_id`, `location` | `{ success }` | Asignar ubicación |

**Estructura de item de menú:**
```json
{
  "title": "Texto del enlace",
  "url": "/ruta",
  "icon": "dashicons-cart",
  "object_id": 123,
  "object": "page"
}
```

### Configuración General

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/site/settings` | - | `{ blogname, tagline, timezone, date_format, ... }` | Configuración del sitio |
| POST | `/site/settings` | `blogname?`, `tagline?`, etc. | `{ success, settings }` | Actualizar configuración |
| POST | `/site/apply-config` | `layout`, `menu`, `settings` | `{ success, applied }` | Aplicar config completa |
| POST | `/site/generate-script` | `format?`: 'bash'|'json' | `{ script }` | Generar script de config |

---

## Module Manager API

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

### Consultas

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/modules/available` | `category?` | `{ modules: [], by_category: {}, categories: [], total, active_count }` | Todos los módulos |
| GET | `/modules/active` | - | `{ modules: [], count }` | Módulos activos |
| GET | `/modules/{module_id}/config` | - | `{ config: {} }` | Configuración de módulo |
| GET | `/modules/{module_id}/stats` | - | `{ records, users, activity }` | Estadísticas |
| GET | `/modules/recommendations` | `use_case?` | `{ recommended: [], bundles: [] }` | Recomendaciones |
| GET | `/modules/profiles` | - | `{ profiles: [] }` | Perfiles predefinidos |

### Acciones

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| POST | `/modules/{module_id}/activate` | - | `{ success, module_id, tables_created }` | Activar módulo |
| POST | `/modules/{module_id}/deactivate` | - | `{ success, module_id }` | Desactivar módulo |
| POST | `/modules/activate-batch` | `modules`: array | `{ success, activated, already_active, failed, total_active }` | Activar múltiples |
| PUT | `/modules/{module_id}/config` | `config`: object | `{ success, config }` | Actualizar config |
| POST | `/modules/{module_id}/demo-data` | `count?`: int (default: 10) | `{ success, created: { type: count } }` | Generar datos demo |
| POST | `/modules/apply-profile` | `profile`: string | `{ success, activated, profile }` | Aplicar perfil |

### Categorías de Módulos

| Categoría | Módulos Incluidos |
|-----------|-------------------|
| `comunicacion` | chat-interno, foros, avisos-municipales, chat-grupos, chat-estados |
| `comunidad` | eventos, socios, comunidades, colectivos, red-social |
| `economia` | grupos-consumo, marketplace, banco-tiempo, crowdfunding, economia-don, economia-suficiencia |
| `formacion` | cursos, talleres, biblioteca, saberes-ancestrales |
| `ecologia` | huertos-urbanos, compostaje, reciclaje, energia-comunitaria, biodiversidad-local |
| `participacion` | encuestas, participacion, presupuestos-participativos, transparencia, campanias |
| `servicios` | reservas, espacios-comunes, incidencias, tramites, fichaje-empleados |
| `multimedia` | podcast, radio, multimedia, kulturaka |
| `movilidad` | carpooling, bicicletas-compartidas, parkings |
| `otros` | clientes, facturas, marketplace, documentacion-legal |

---

## VBP Claude API (Visual Page Builder)

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

### Información del Sistema

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/claude/schema` | - | `{ blocks: { type: { name, category, fields, presets } } }` | Schema de bloques |
| GET | `/claude/blocks` | `category?` | `{ blocks: [], categories: [] }` | Lista bloques |
| GET | `/claude/modules` | - | `{ modules: [] }` | Módulos activos |
| GET | `/claude/templates` | - | `{ templates: [] }` | Plantillas de página |
| GET | `/claude/section-types` | - | `{ types: [] }` | Tipos de sección |
| GET | `/claude/design-presets` | - | `{ presets: { name: { colors, fonts, spacing } } }` | Presets de diseño |
| GET | `/claude/capabilities` | - | `{ blocks, sections, animations, widgets }` | Capacidades |
| GET | `/claude/widgets` | - | `{ widgets: [] }` | Widgets de módulos |
| GET | `/claude/status` | - | `{ vbp_loaded, blocks_count, version }` | Estado del sistema |
| GET | `/claude/languages` | - | `{ languages: [], default }` | Idiomas disponibles |
| GET | `/claude/blocks/{type}/presets` | - | `{ presets: [] }` | Presets de un bloque |

### Páginas

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/claude/pages` | `status?`: 'any'|'publish'|'draft' | `{ pages: [{ id, title, url, status, elements_count }] }` | Listar páginas |
| GET | `/claude/pages/{id}` | - | `{ id, title, content, elements, settings, status, url }` | Obtener página |
| POST | `/claude/pages` | Ver abajo | `{ success, page_id, title, url, edit_url }` | **Crear página** |
| PUT | `/claude/pages/{id}` | `title?`, `elements?`, `status?`, `settings?`, `design_preset?` | `{ success, page_id }` | Actualizar página |
| POST | `/claude/pages/{id}/blocks` | `type`, `data`: object, `position?`: 'end'|'start'|int | `{ success, block_id }` | Añadir bloque |
| POST | `/claude/pages/{id}/duplicate` | `title?` | `{ success, new_page_id, url }` | Duplicar página |
| POST | `/claude/pages/{id}/publish` | - | `{ success, url }` | Publicar página |
| GET | `/claude/pages/{id}/url` | - | `{ url, edit_url }` | Obtener URLs |
| POST | `/claude/pages/{id}/translate` | `to_lang`, `save?`: bool | `{ success, translated_id?, content }` | Traducir página |
| GET | `/claude/pages/{id}/validate-animations` | - | `{ valid, warnings }` | Validar animaciones |

**Parámetros de `POST /claude/pages`:**
```json
{
  "title": "Título de la página",
  "elements": [{ "type": "hero", "data": {} }],
  "template": "landing",
  "context": { "topic": "...", "industry": "..." },
  "status": "draft|publish",
  "settings": { "pageWidth": "1200px", "backgroundColor": "#fff" },
  "design_preset": "modern|corporate|minimal|dark|vibrant|elegant|tech|nature"
}
```

### Generación

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| POST | `/claude/generate-section` | `type`, `context?`: object | `{ elements: [] }` | Generar sección |
| POST | `/claude/pages/styled` | `title`, `preset`, `sections`: array, `context?`, `status?` | `{ success, page_id, url, elements_created }` | Crear página estilizada |
| POST | `/claude/validate-elements` | `elements`: array | `{ valid, errors, warnings }` | Validar elementos |
| POST | `/claude/flush-permalinks` | - | `{ success }` | Regenerar permalinks |

### Tipos de Bloques Disponibles

| Tipo | Descripción | Campos Principales |
|------|-------------|-------------------|
| `hero` | Cabecera con CTA | title, subtitle, cta_text, cta_url, background_image |
| `text` | Bloque de texto | content, alignment |
| `image` | Imagen | src, alt, caption |
| `gallery` | Galería | images: array |
| `features` | Características | items: [{ icon, title, text }] |
| `services` | Servicios | items: [{ icon, title, description, price }] |
| `testimonials` | Testimonios | items: [{ quote, author, company, photo }] |
| `team` | Equipo | members: [{ name, role, photo, social }] |
| `pricing` | Precios | plans: [{ name, price, features, cta }] |
| `faq` | Preguntas frecuentes | items: [{ question, answer }] |
| `contact` | Contacto | form_fields, email, phone, address |
| `cta` | Call to action | title, text, button_text, button_url |
| `stats` | Estadísticas | items: [{ number, label }] |
| `timeline` | Cronología | items: [{ date, title, description }] |
| `video` | Video | url, autoplay, controls |
| `map` | Mapa | latitude, longitude, zoom |
| `module_widget` | Widget de módulo | module_id, widget_type |

---

## Media API

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/media/library` | `per_page?`, `page?`, `mime_type?` | `{ items: [{ id, url, title, alt, mime_type }], total }` | Biblioteca de medios |
| POST | `/media/upload-url` | `url`, `title?`, `alt?` | `{ success, attachment_id, url }` | Subir desde URL |
| GET | `/media/icons` | `type?`: 'emoji'|'dashicons'|'all' | `{ emoji: [], dashicons: [] }` | Catálogo de iconos |
| GET | `/media/placeholders` | `category?` | `{ images: [] }` | Imágenes placeholder |
| GET | `/media/search-stock` | `query`, `per_page?`: int | `{ images: [{ url, thumb, credit }] }` | Buscar en Unsplash |
| GET | `/media/fonts` | `category?` | `{ fonts: [{ family, category, variants }] }` | Google Fonts |
| GET | `/media/gradients` | - | `{ gradients: [{ name, css }] }` | Gradientes predefinidos |
| GET | `/media/color-palettes` | - | `{ palettes: [{ name, colors }] }` | Paletas de colores |
| POST | `/media/generate-placeholder` | `width`, `height`, `text?`, `bg_color?`, `text_color?` | `{ url }` | Generar placeholder |

---

## SEO API

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

### Configuración Global

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/config` | - | `{ defaults, social_profiles, verification_codes }` | Config global |
| POST | `/seo/config` | `defaults?`, `social_profiles?`, `verification_codes?` | `{ success }` | Actualizar config |
| GET | `/seo/sitemap` | - | `{ enabled, post_types, taxonomies, exclude }` | Config sitemap |
| POST | `/seo/sitemap` | Ver campos | `{ success }` | Actualizar sitemap |
| GET | `/seo/robots` | - | `{ content }` | robots.txt |
| POST | `/seo/robots` | `content` | `{ success }` | Actualizar robots.txt |
| GET | `/seo/redirects` | - | `{ redirects: [{ from, to, type }] }` | Redirecciones |
| POST | `/seo/redirects` | `from`, `to`, `type`: 301|302 | `{ success, id }` | Añadir redirección |
| DELETE | `/seo/redirects/{id}` | - | `{ success }` | Eliminar redirección |

### Por Página

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/pages/{id}` | - | `{ title, description, keywords, canonical, robots, focus_keyword }` | SEO de página |
| POST | `/seo/pages/{id}` | `title?`, `description?`, `keywords?`, `canonical?`, `robots?`, `focus_keyword?` | `{ success }` | Actualizar SEO |
| POST | `/seo/pages/{id}/analyze` | `focus_keyword?` | `{ score, issues, suggestions }` | Analizar SEO |
| POST | `/seo/pages/{id}/generate` | `style?`: 'concise'|'detailed' | `{ title, description, keywords }` | Auto-generar metadatos |

### Open Graph

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/pages/{id}/opengraph` | - | `{ og_title, og_description, og_image, og_type }` | Config OG |
| POST | `/seo/pages/{id}/opengraph` | `og_title?`, `og_description?`, `og_image?`, `og_type?` | `{ success }` | Actualizar OG |

### Twitter Cards

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/pages/{id}/twitter` | - | `{ card, title, description, image }` | Config Twitter |
| POST | `/seo/pages/{id}/twitter` | `card?`: 'summary'|'summary_large_image', `title?`, `description?`, `image?` | `{ success }` | Actualizar Twitter |

### Schema.org

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/pages/{id}/schema` | - | `{ type, data }` | JSON-LD |
| POST | `/seo/pages/{id}/schema` | `type`, `data`: object | `{ success }` | Actualizar Schema |

**Tipos de Schema disponibles:**
- `Article`, `BlogPosting`, `NewsArticle`
- `Product`, `Service`, `Offer`
- `LocalBusiness`, `Organization`, `Person`
- `Event`, `Course`, `Recipe`
- `FAQPage`, `HowTo`, `BreadcrumbList`

### Utilidades

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/seo/presets` | - | `{ presets: { type: { title_template, description_template, schema_type } } }` | Presets por tipo |
| POST | `/seo/bulk-update` | `pages`: [{ id, seo_data }] | `{ success, updated }` | Actualización masiva |
| GET | `/seo/analysis/site` | - | `{ score, issues, pages_without_seo }` | Análisis del sitio |

---

## App Config API

**Base:** `https://tu-sitio.com/wp-json/flavor-vbp/v1`

### Configuración General

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/config` | - | `{ branding, theme, modules, permissions, build_settings }` | Config completa |
| POST | `/app/config` | Ver secciones abajo | `{ success }` | Actualizar config |

### Branding

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/branding` | - | `{ app_name, app_id, logo_url, splash_url, icon_url }` | Branding |
| POST | `/app/branding` | `app_name?`, `app_id?`, `logo_url?`, `splash_url?`, `icon_url?`, `primary_color?`, `secondary_color?` | `{ success }` | Actualizar branding |

### Tema Visual

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/theme` | - | `{ preset, light: {}, dark: {} }` | Tema |
| POST | `/app/theme` | `preset?`, `light?`: object, `dark?`: object | `{ success }` | Actualizar tema |

**Presets de tema:**
- `modern-blue`, `emerald-green`, `purple-violet`, `sunset-orange`
- `ocean-teal`, `rose-pink`, `slate-gray`, `forest-green`

### Módulos de App

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/modules` | - | `{ enabled: [], available: [] }` | Módulos |
| POST | `/app/modules` | `enabled`: array | `{ success }` | Configurar módulos |

### Permisos

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/permissions` | - | `{ required: [], optional: [] }` | Permisos |
| POST | `/app/permissions` | `required?`: array, `optional?`: array | `{ success }` | Actualizar permisos |

### Build

| Método | Endpoint | Parámetros | Respuesta | Descripción |
|--------|----------|------------|-----------|-------------|
| GET | `/app/build-settings` | - | `{ android: {}, ios: {} }` | Build settings |
| POST | `/app/build-settings` | `android?`: object, `ios?`: object | `{ success }` | Actualizar build |
| GET | `/app/layouts` | - | `{ screens: {} }` | Layouts de pantalla |
| POST | `/app/export-dart` | `files?`: array | `{ files: { filename: content } }` | Exportar código Dart |

---

## Códigos de Estado HTTP

| Código | Significado |
|--------|-------------|
| `200` | Éxito - GET, PUT |
| `201` | Creado - POST |
| `400` | Bad Request - Parámetros inválidos |
| `403` | Forbidden - API key inválida |
| `404` | Not Found - Recurso no existe |
| `500` | Internal Error - Error del servidor |

## Estructura de Error

```json
{
  "code": "error_code_string",
  "message": "Descripción legible del error",
  "data": {
    "status": 400,
    "details": {}
  }
}
```

---

## Rate Limits

No hay rate limits configurados por defecto. Para entornos de producción se recomienda:
- Máximo 100 requests/minuto por IP
- Máximo 1000 requests/hora por API key

---

## Versionado

- **Versión actual:** v1
- **Formato:** `flavor-{api}/v{version}`
- Las APIs mantienen compatibilidad hacia atrás dentro de la misma versión major
