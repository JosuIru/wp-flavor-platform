# Guía de APIs para Claude Code

Esta guía documenta todas las APIs REST disponibles en Flavor Chat IA para que Claude Code pueda crear y configurar sitios de forma automática.

## Autenticación

Todas las APIs requieren autenticación mediante una clave de API.

### Métodos de autenticación

**1. Header HTTP (recomendado):**
```bash
curl -H "X-VBP-Key: <tu-api-key>" https://tu-sitio.com/wp-json/flavor-vbp/v1/endpoint
```

**2. Parámetro de query:**
```bash
curl "https://tu-sitio.com/wp-json/flavor-vbp/v1/endpoint?api_key=<tu-api-key>"
```

### Clave de API

La clave de API debe obtenerse desde la instalación actual. Puede consultarse o regenerarse en:
- Ajustes del plugin > API Settings > VBP API Key

---

## Namespaces Disponibles

| Namespace | Propósito | Clase |
|-----------|-----------|-------|
| `flavor-site-builder/v1` | Orquestador principal para crear sitios | `Flavor_Site_Builder_API` |
| `flavor-vbp/v1` | Configuración de sitio, módulos, páginas, SEO, media, apps | Múltiples clases |
| `flavor/v1` | Dashboard de cliente | `Flavor_Client_Dashboard_API` |
| `chat-ia-mobile/v1` | API móvil Flutter | `Flavor_Mobile_API` |

---

## APIs por Categoría

### 1. Site Builder API

**Namespace:** `flavor-site-builder/v1`

El orquestador principal que integra todas las herramientas para crear sitios completos.

#### Endpoints de Información

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/profiles` | Lista perfiles de aplicación disponibles |
| GET | `/templates` | Lista plantillas de sitio disponibles |
| GET | `/themes` | Lista temas de diseño (colores, estilos) |
| GET | `/modules` | Lista todos los módulos del sistema |
| GET | `/config-template/{template}` | Obtiene configuración completa de una plantilla |
| GET | `/design/options` | Opciones de personalización de diseño |
| GET | `/animations` | Animaciones y efectos disponibles |
| GET | `/tools` | Herramientas del sistema disponibles |
| GET | `/site/status` | Estado actual del sitio configurado |

#### Endpoints de Creación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/site/create` | **Crear sitio completo** desde plantilla |
| POST | `/analyze` | Analizar requisitos con IA |
| POST | `/pages/create-for-modules` | Crear páginas para módulos activos |
| POST | `/menu` | Crear menú de navegación |
| POST | `/modules/activate` | Activar módulos |
| POST | `/profile/set` | Establecer perfil de aplicación |
| POST | `/theme/apply` | Aplicar tema de diseño |
| POST | `/demo-data/import` | Importar datos de demostración |

#### Endpoints de Validación y Exportación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/site/validate` | Validar configuración antes de aplicar |
| GET | `/site/export` | Exportar configuración actual del sitio |
| POST | `/site/import` | Importar configuración desde JSON |
| GET | `/system/health` | Health check del sistema |

#### Ejemplo: Crear sitio completo

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "site_name": "Cooperativa Verde",
    "theme": "light-eco",
    "import_demo": true,
    "create_menu": true,
    "create_pages": true
  }' \
  https://tu-sitio.com/wp-json/flavor-site-builder/v1/site/create
```

**Respuesta:**
```json
{
  "success": true,
  "steps": {
    "profile": { "success": true },
    "modules": { "activated": ["grupos-consumo", "socios", "eventos"] },
    "pages": { "created": 5 },
    "menu": { "menu_id": 123 },
    "theme": { "applied": "light-eco" },
    "demo_data": { "records": 50 }
  },
  "site_url": "https://tu-sitio.com"
}
```

#### Ejemplo: Validar configuración antes de aplicar

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "theme": "modern",
    "modules": ["socios", "eventos", "modulo-inexistente"]
  }' \
  https://tu-sitio.com/wp-json/flavor-site-builder/v1/site/validate
```

**Respuesta:**
```json
{
  "valid": true,
  "errors": [],
  "warnings": [
    {
      "field": "modules",
      "message": "Módulo \"modulo-inexistente\" no reconocido"
    }
  ]
}
```

#### Ejemplo: Exportar configuración

```bash
curl -H "X-VBP-Key: $API_KEY" \
  "https://tu-sitio.com/wp-json/flavor-site-builder/v1/site/export?include_content=true"
```

#### Ejemplo: Health check

```bash
curl -H "X-VBP-Key: $API_KEY" \
  https://tu-sitio.com/wp-json/flavor-site-builder/v1/system/health
```

**Respuesta:**
```json
{
  "status": "healthy",
  "timestamp": "2024-12-15T10:30:00+00:00",
  "version": "2.2.0",
  "php": "8.1.0",
  "wp": "6.4.2",
  "checks": {
    "database": { "status": "ok", "tables": {...} },
    "modules": { "status": "ok", "active": 12 },
    "vbp": { "status": "ok", "available": true }
  }
}
```

---

### 2. Site Config API

**Namespace:** `flavor-vbp/v1`

Configuración de layouts, menús y ajustes del sitio.

#### Endpoints de Layouts

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/site/layouts` | Obtener layouts disponibles y configuración actual |
| POST | `/site/layouts/active` | Configurar layout activo (menú + footer) |
| POST | `/site/layouts/settings` | Actualizar ajustes de layout |

#### Endpoints de Menús

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/site/menus` | Listar menús de WordPress |
| POST | `/site/menus` | Crear menú con items |
| PUT | `/site/menus/{id}` | Actualizar menú existente |
| POST | `/site/menus/locations` | Asignar menú a ubicación del tema |

#### Endpoints de Configuración General

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/site/settings` | Obtener configuración del sitio |
| POST | `/site/settings` | Actualizar configuración |
| POST | `/site/apply-config` | Aplicar configuración completa |
| POST | `/site/generate-script` | Generar script de configuración |

#### Ejemplo: Crear menú

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "location": "primary",
    "items": [
      { "title": "Inicio", "url": "/" },
      { "title": "Productos", "url": "/productos", "icon": "dashicons-cart" },
      { "title": "Eventos", "url": "/eventos", "icon": "dashicons-calendar" },
      { "title": "Contacto", "url": "/contacto" }
    ]
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/site/menus
```

#### Ejemplo: Configurar layout

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "menu": "mega-menu",
    "footer": "multi-column"
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/site/layouts/active
```

---

### 3. Module Manager API

**Namespace:** `flavor-vbp/v1`

Activación, configuración y gestión de módulos.

#### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/modules/available` | Lista todos los módulos con estado |
| GET | `/modules/active` | Lista módulos actualmente activos |
| POST | `/modules/{module_id}/activate` | Activar un módulo |
| POST | `/modules/{module_id}/deactivate` | Desactivar un módulo |
| POST | `/modules/activate-batch` | Activar múltiples módulos |
| GET | `/modules/{module_id}/config` | Obtener configuración de módulo |
| PUT | `/modules/{module_id}/config` | Actualizar configuración |
| POST | `/modules/{module_id}/demo-data` | Generar datos de demostración |
| GET | `/modules/{module_id}/stats` | Estadísticas del módulo |
| GET | `/modules/recommendations` | Recomendaciones según caso de uso |
| POST | `/modules/apply-profile` | Aplicar perfil predefinido |
| GET | `/modules/profiles` | Listar perfiles disponibles |

#### Categorías de Módulos

| Categoría | Módulos |
|-----------|---------|
| **comunicacion** | chat-interno, foros, avisos-municipales |
| **comunidad** | eventos, socios, comunidades, colectivos |
| **economia** | grupos-consumo, marketplace, banco-tiempo, crowdfunding, economia-don |
| **formacion** | cursos, talleres, biblioteca |
| **ecologia** | huertos-urbanos, compostaje, reciclaje, energia-comunitaria |
| **participacion** | encuestas, participacion, presupuestos-participativos, transparencia |
| **servicios** | reservas, espacios-comunes, incidencias, tramites |
| **multimedia** | podcast, radio, multimedia |

#### Ejemplo: Activar módulos en lote

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["eventos", "socios", "foros", "marketplace"]
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/modules/activate-batch
```

#### Ejemplo: Generar datos demo

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{ "count": 20 }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/modules/eventos/demo-data
```

---

### 4. VBP Claude API (Visual Page Builder)

**Namespace:** `flavor-vbp/v1`

Crear y gestionar páginas visuales con bloques.

#### Endpoints de Información

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/claude/schema` | Schema completo de bloques disponibles |
| GET | `/claude/blocks` | Lista bloques por categoría |
| GET | `/claude/modules` | Módulos activos para bloques |
| GET | `/claude/templates` | Plantillas de página disponibles |
| GET | `/claude/section-types` | Tipos de sección para generar |
| GET | `/claude/design-presets` | Presets de diseño (modern, corporate, etc.) |
| GET | `/claude/capabilities` | Capacidades completas del sistema |
| GET | `/claude/widgets` | Widgets de módulos disponibles |
| GET | `/claude/status` | Estado del sistema VBP |
| GET | `/claude/languages` | Idiomas disponibles |

#### Endpoints de Páginas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/claude/pages` | Listar páginas VBP |
| POST | `/claude/pages` | **Crear página** con elementos |
| GET | `/claude/pages/{id}` | Obtener página |
| PUT | `/claude/pages/{id}` | Actualizar página |
| POST | `/claude/pages/{id}/blocks` | Añadir bloque a página |
| POST | `/claude/pages/{id}/duplicate` | Duplicar página |
| POST | `/claude/pages/{id}/publish` | Publicar página |
| GET | `/claude/pages/{id}/url` | Obtener URL pública |
| POST | `/claude/pages/{id}/translate` | Traducir página a otro idioma |
| GET | `/claude/pages/{id}/validate-animations` | Validar animaciones |

#### Endpoints de Generación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/claude/generate-section` | Generar sección desde plantilla |
| POST | `/claude/pages/styled` | Crear página con preset de diseño |
| POST | `/claude/validate-elements` | Validar elementos antes de crear |
| POST | `/claude/flush-permalinks` | Regenerar permalinks |
| GET | `/claude/blocks/{type}/presets` | Obtener presets de un bloque |

#### Presets de Diseño

| Preset | Descripción |
|--------|-------------|
| `modern` | Estilo moderno con gradientes sutiles |
| `corporate` | Profesional y empresarial |
| `minimal` | Minimalista y limpio |
| `dark` | Tema oscuro |
| `vibrant` | Colores vibrantes y llamativos |
| `elegant` | Elegante y sofisticado |
| `tech` | Tecnológico y futurista |
| `nature` | Natural y orgánico |

#### Ejemplo: Crear página con secciones

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Bienvenidos a nuestra Cooperativa",
    "template": "landing",
    "design_preset": "nature",
    "status": "publish",
    "context": {
      "topic": "cooperativa de consumo ecológico",
      "industry": "alimentación"
    },
    "elements": [
      {
        "type": "hero",
        "data": {
          "title": "Consumo Responsable",
          "subtitle": "Productos locales y ecológicos",
          "cta_text": "Únete",
          "cta_url": "/registro"
        }
      },
      {
        "type": "features",
        "data": {
          "items": [
            { "icon": "leaf", "title": "Ecológico", "text": "100% productos eco" },
            { "icon": "users", "title": "Comunidad", "text": "+500 familias" },
            { "icon": "truck", "title": "Reparto", "text": "Semanal a domicilio" }
          ]
        }
      }
    ]
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/claude/pages
```

#### Ejemplo: Crear página estilizada

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nuestros Servicios",
    "preset": "corporate",
    "sections": ["hero", "services", "testimonials", "cta"],
    "context": {
      "business_name": "Cooperativa Verde",
      "services": ["Productos eco", "Talleres", "Eventos"]
    },
    "status": "draft"
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/claude/pages/styled
```

---

### 5. Media API

**Namespace:** `flavor-vbp/v1`

Gestión de imágenes, iconos y recursos multimedia.

#### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/media/library` | Biblioteca de medios de WordPress |
| POST | `/media/upload-url` | Subir imagen desde URL |
| GET | `/media/icons` | Catálogo de iconos (emoji + dashicons) |
| GET | `/media/placeholders` | Imágenes placeholder |
| GET | `/media/search-stock` | Buscar en Unsplash |
| GET | `/media/fonts` | Google Fonts disponibles |
| GET | `/media/gradients` | Gradientes predefinidos |
| GET | `/media/color-palettes` | Paletas de colores |
| POST | `/media/generate-placeholder` | Generar placeholder personalizado |

#### Ejemplo: Buscar imágenes

```bash
curl -H "X-VBP-Key: $API_KEY" \
  "https://tu-sitio.com/wp-json/flavor-vbp/v1/media/search-stock?query=vegetables&per_page=10"
```

#### Ejemplo: Subir imagen desde URL

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://images.unsplash.com/photo-123...",
    "title": "Verduras frescas",
    "alt": "Cesta de verduras ecológicas"
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/media/upload-url
```

---

### 6. SEO API

**Namespace:** `flavor-vbp/v1`

Configuración completa de SEO, Open Graph, Schema.org.

#### Endpoints de Configuración Global

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/seo/config` | Configuración SEO global |
| POST | `/seo/config` | Actualizar configuración global |
| GET | `/seo/sitemap` | Configuración del sitemap |
| POST | `/seo/sitemap` | Actualizar sitemap |
| GET | `/seo/robots` | Contenido de robots.txt |
| POST | `/seo/robots` | Actualizar robots.txt |
| GET | `/seo/redirects` | Lista de redirecciones |
| POST | `/seo/redirects` | Añadir redirección |

#### Endpoints por Página

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/seo/pages/{id}` | SEO de una página |
| POST | `/seo/pages/{id}` | Actualizar SEO de página |
| POST | `/seo/pages/{id}/analyze` | Analizar SEO de página |
| POST | `/seo/pages/{id}/generate` | Auto-generar metadatos |

#### Endpoints de Open Graph y Schema

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/seo/pages/{id}/opengraph` | Configuración Open Graph |
| POST | `/seo/pages/{id}/opengraph` | Actualizar Open Graph |
| GET | `/seo/pages/{id}/twitter` | Twitter Cards |
| POST | `/seo/pages/{id}/twitter` | Actualizar Twitter Cards |
| GET | `/seo/pages/{id}/schema` | Schema.org / JSON-LD |
| POST | `/seo/pages/{id}/schema` | Actualizar Schema |

#### Endpoints Adicionales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/seo/presets` | Presets SEO por tipo de página |
| POST | `/seo/bulk-update` | Actualización masiva |
| GET | `/seo/analysis/site` | Análisis SEO del sitio completo |

#### Presets SEO Disponibles

| Preset | Uso recomendado |
|--------|-----------------|
| `article` | Artículos y posts |
| `landing` | Landing pages |
| `product` | Páginas de producto |
| `service` | Páginas de servicios |
| `contact` | Página de contacto |
| `about` | Página "Sobre nosotros" |
| `event` | Eventos |
| `local_business` | Negocios locales |

#### Ejemplo: Configurar SEO de página

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cooperativa Verde - Consumo Ecológico",
    "description": "Únete a nuestra cooperativa de consumo responsable. Productos locales, ecológicos y de temporada.",
    "keywords": ["cooperativa", "ecológico", "consumo responsable"],
    "canonical": "https://cooperativaverde.com/",
    "robots": "index, follow"
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/seo/pages/123
```

#### Ejemplo: Configurar Schema.org

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "LocalBusiness",
    "data": {
      "name": "Cooperativa Verde",
      "description": "Cooperativa de consumo ecológico",
      "address": {
        "streetAddress": "Calle Verde 123",
        "addressLocality": "Madrid",
        "postalCode": "28001"
      },
      "telephone": "+34 91 123 4567",
      "openingHours": "Mo-Fr 09:00-20:00"
    }
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/seo/pages/123/schema
```

---

### 7. App Config API (Flutter/Mobile)

**Namespace:** `flavor-vbp/v1`

Configuración para apps móviles Flutter.

#### Endpoints de Configuración

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/app/config` | Configuración completa de la app |
| POST | `/app/config` | Actualizar configuración |
| GET | `/app/branding` | Logos, colores, nombre |
| POST | `/app/branding` | Actualizar branding |
| GET | `/app/theme` | Tema visual (light/dark) |
| POST | `/app/theme` | Actualizar tema |
| GET | `/app/modules` | Módulos habilitados en app |
| POST | `/app/modules` | Configurar módulos |
| GET | `/app/permissions` | Permisos requeridos |
| POST | `/app/permissions` | Actualizar permisos |

#### Endpoints de Build

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/app/build-settings` | Configuración de compilación |
| POST | `/app/build-settings` | Actualizar build settings |
| GET | `/app/layouts` | Layouts de pantalla |
| POST | `/app/export-dart` | Exportar código Dart |

#### Presets de Tema

| Preset | Colores principales |
|--------|---------------------|
| `modern-blue` | Azul índigo + rosa |
| `emerald-green` | Verde esmeralda + teal |
| `purple-violet` | Púrpura + violeta |
| `sunset-orange` | Naranja + rojo |
| `ocean-teal` | Teal + cyan |
| `rose-pink` | Rosa + fucsia |
| `slate-gray` | Gris + azul |
| `forest-green` | Verde bosque + lima |

#### Ejemplo: Configurar branding de app

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "Cooperativa Verde",
    "app_id": "com.cooperativaverde.app",
    "logo_url": "https://cooperativaverde.com/logo.png",
    "primary_color": "#059669",
    "secondary_color": "#84cc16"
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/app/branding
```

#### Ejemplo: Exportar configuración Dart

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "files": ["app_config", "app_colors", "app_theme"]
  }' \
  https://tu-sitio.com/wp-json/flavor-vbp/v1/app/export-dart
```

---

## Códigos de Respuesta

| Código | Significado |
|--------|-------------|
| 200 | Éxito |
| 201 | Recurso creado |
| 400 | Parámetros inválidos |
| 403 | API key inválida |
| 404 | Recurso no encontrado |
| 500 | Error del servidor |

## Estructura de Errores

```json
{
  "code": "error_code",
  "message": "Descripción del error",
  "data": {
    "status": 400
  }
}
```

---

## Flujos de Trabajo Recomendados

### Flujo 1: Crear sitio desde plantilla

1. `GET /flavor-site-builder/v1/templates` - Ver plantillas disponibles
2. `GET /flavor-site-builder/v1/config-template/{template}` - Obtener configuración
3. `POST /flavor-site-builder/v1/site/create` - Crear sitio completo

### Flujo 2: Configuración manual paso a paso

1. `POST /flavor-vbp/v1/modules/activate-batch` - Activar módulos
2. `POST /flavor-vbp/v1/site/menus` - Crear menú
3. `POST /flavor-vbp/v1/claude/pages` - Crear páginas
4. `POST /flavor-vbp/v1/seo/pages/{id}` - Configurar SEO
5. `POST /flavor-site-builder/v1/theme/apply` - Aplicar tema

### Flujo 3: Crear landing page

1. `GET /flavor-vbp/v1/claude/design-presets` - Ver presets
2. `GET /flavor-vbp/v1/claude/section-types` - Ver secciones disponibles
3. `POST /flavor-vbp/v1/claude/pages/styled` - Crear página estilizada
4. `POST /flavor-vbp/v1/claude/pages/{id}/publish` - Publicar

---

## Información Adicional

- **Versión API:** 2.2.0
- **Documentación completa de endpoints:** Ver `ENDPOINTS-REFERENCE.md`
- **Tutorial paso a paso:** Ver `WORKFLOW-CREAR-SITIO.md`
