# Flavor Chat IA - Referencia de APIs REST

## Namespaces Disponibles

| Namespace | Descripción |
|-----------|-------------|
| `flavor-vbp/v1` | Visual Builder Pro - Documentos y bloques |
| `flavor-site-builder/v1` | Site Builder - Creación de sitios completos |
| `flavor-modules/v1` | Gestión de módulos |
| `flavor-chat/v1` | Chat IA |

---

## Visual Builder Pro API

**Base:** `/wp-json/flavor-vbp/v1`

### Documentos

#### GET /documents/{id}
Obtiene un documento VBP por ID.

**Respuesta:**
```json
{
  "id": 123,
  "title": "Mi Landing",
  "elements": [...],
  "settings": {...},
  "modified": "2024-01-15T10:30:00"
}
```

#### POST /documents/{id}
Guarda/actualiza un documento VBP.

**Body:**
```json
{
  "title": "Mi Landing Actualizada",
  "elements": [
    {
      "id": "el_abc123",
      "type": "hero",
      "variant": "fullscreen",
      "data": {...},
      "styles": {...}
    }
  ],
  "settings": {
    "pageWidth": "1200",
    "backgroundColor": "#ffffff",
    "fullWidth": true,
    "customCss": ""
  }
}
```

#### GET /documents/{id}/revisions
Lista revisiones de un documento.

#### POST /documents/{id}/revisions/{revision_id}/restore
Restaura una revisión anterior.

### Bloques

#### GET /blocks
Lista todos los bloques disponibles.

**Parámetros:**
- `category` - Filtrar por categoría
- `module` - Filtrar por módulo
- `search` - Buscar por nombre

**Respuesta:**
```json
{
  "blocks": [
    {
      "id": "hero",
      "name": "Hero",
      "category": "sections",
      "icon": "<svg>...</svg>",
      "variants": ["fullscreen", "split", "centered"],
      "fields": {...},
      "presets": {...}
    }
  ],
  "categories": [
    {"id": "sections", "name": "Secciones", "order": 10},
    {"id": "basic", "name": "Básicos", "order": 20}
  ]
}
```

#### GET /blocks/schema
Obtiene el schema JSON completo de todos los bloques.

**Respuesta:**
```json
{
  "version": "2.0.0",
  "blocks": {
    "hero": {
      "type": "object",
      "properties": {
        "titulo": {"type": "string"},
        "subtitulo": {"type": "string"},
        "boton_texto": {"type": "string"}
      }
    }
  }
}
```

### Templates

#### GET /templates
Lista templates disponibles.

#### POST /templates
Crea un nuevo template.

**Body:**
```json
{
  "title": "Mi Template",
  "category": "landing",
  "elements": [...],
  "settings": {...}
}
```

#### POST /documents/{id}/apply-template
Aplica un template a un documento.

**Body:**
```json
{
  "template_id": "starter-landing"
}
```

### Renderizado

#### POST /render-element
Renderiza un elemento individual (para preview).

**Body:**
```json
{
  "type": "hero",
  "variant": "fullscreen",
  "data": {...}
}
```

#### POST /preview-shortcode
Previsualiza un shortcode renderizado.

**Body:**
```json
{
  "shortcode": "[gc_catalogo limite=\"4\"]"
}
```

#### POST /documents/{id}/export-html
Exporta documento como HTML estático.

---

## Site Builder API

**Base:** `/wp-json/flavor-site-builder/v1`

### Creación de Sitios

#### POST /site/create
Crea un sitio completo desde template.

**Body:**
```json
{
  "nombre_sitio": "Mi Sitio",
  "template": "grupo_consumo",
  "perfil": "eco_community",
  "modulos": ["grupos_consumo", "eventos"],
  "diseño": {
    "primary_color": "#22c55e",
    "secondary_color": "#15803d",
    "font_family": "Inter",
    "border_radius": "lg"
  },
  "demo_data": true,
  "crear_menu": true,
  "crear_paginas": true
}
```

**Respuesta:**
```json
{
  "success": true,
  "site": {
    "name": "Mi Sitio",
    "pages_created": 8,
    "pages": [
      {"id": 123, "title": "Inicio", "slug": "inicio"},
      {"id": 124, "title": "Productos", "slug": "productos"}
    ],
    "menu_id": 456,
    "landing_id": 789
  }
}
```

#### POST /site/validate
Valida configuración antes de crear.

**Body:**
```json
{
  "template": "grupo_consumo",
  "modulos": ["grupos_consumo"]
}
```

**Respuesta:**
```json
{
  "valid": true,
  "warnings": [],
  "required_modules": ["grupos_consumo"],
  "optional_modules": ["eventos", "socios"]
}
```

#### GET /site/export
Exporta configuración del sitio actual.

#### POST /site/import
Importa configuración de sitio.

### Módulos

#### GET /modules
Lista módulos instalados y su estado.

**Respuesta:**
```json
{
  "modules": [
    {
      "id": "grupos_consumo",
      "name": "Grupos de Consumo",
      "description": "...",
      "active": true,
      "has_frontend": true,
      "has_dashboard": true,
      "shortcodes": ["gc_catalogo", "gc_grupos_lista"],
      "widgets": ["gc_stats", "gc_productos"]
    }
  ]
}
```

#### POST /modules/activate
Activa módulos.

**Body:**
```json
{
  "modules": ["grupos_consumo", "eventos"]
}
```

#### POST /modules/deactivate
Desactiva módulos.

### Templates y Perfiles

#### GET /templates
Lista plantillas de sitio disponibles.

#### GET /profiles
Lista perfiles de aplicación.

#### POST /profile/set
Establece perfil de app.

**Body:**
```json
{
  "profile": "eco_community"
}
```

### Páginas

#### POST /pages/create-for-modules
Crea páginas para módulos activos.

**Body:**
```json
{
  "modules": ["grupos_consumo", "eventos"],
  "parent_page": 0
}
```

### Menú

#### POST /menu
Crea o actualiza menú.

**Body:**
```json
{
  "name": "Menu Principal",
  "location": "primary",
  "items": [
    {"title": "Inicio", "url": "/", "order": 1},
    {"title": "Productos", "url": "/productos", "order": 2, "children": [
      {"title": "Catálogo", "url": "/productos/catalogo"}
    ]}
  ]
}
```

### Diseño

#### GET /design/options
Obtiene opciones de diseño disponibles.

#### POST /theme/apply
Aplica tema/diseño.

**Body:**
```json
{
  "preset": "verde_natural",
  "custom": {
    "primary_color": "#22c55e"
  }
}
```

### Demo Data

#### POST /demo-data/import
Importa datos de demostración.

**Body:**
```json
{
  "modules": ["grupos_consumo"],
  "cantidad": "medium"
}
```

---

## Módulos API

**Base:** `/wp-json/flavor-modules/v1`

### Listado

#### GET /list
Lista todos los módulos.

#### GET /{module_id}
Obtiene información de un módulo.

### Configuración

#### GET /{module_id}/config
Obtiene configuración del módulo.

#### POST /{module_id}/config
Actualiza configuración.

### Datos

#### GET /{module_id}/stats
Obtiene estadísticas del módulo.

---

## Autenticación

### Application Passwords (Recomendado)
```bash
curl -X GET "https://sitio.local/wp-json/flavor-vbp/v1/documents/123" \
  -u "usuario:xxxx xxxx xxxx xxxx"
```

### JWT (Si está configurado)
```bash
# Obtener token
curl -X POST "https://sitio.local/wp-json/jwt-auth/v1/token" \
  -d "username=admin&password=contraseña"

# Usar token
curl -X GET "https://sitio.local/wp-json/flavor-vbp/v1/documents/123" \
  -H "Authorization: Bearer eyJ..."
```

---

## Errores Comunes

| Código HTTP | Error | Descripción |
|-------------|-------|-------------|
| 400 | `invalid_request` | Parámetros inválidos |
| 401 | `unauthorized` | No autenticado |
| 403 | `forbidden` | Sin permisos |
| 404 | `not_found` | Recurso no existe |
| 422 | `validation_error` | Error de validación |
| 500 | `server_error` | Error interno |

**Formato de error:**
```json
{
  "code": "invalid_request",
  "message": "El campo 'titulo' es requerido",
  "data": {
    "field": "titulo",
    "status": 400
  }
}
```

---

## Rate Limiting

- **Límite:** 100 requests/minuto por IP
- **Header:** `X-RateLimit-Remaining: 95`

---

## Ejemplos de Uso

### Crear Landing Completa

```bash
#!/bin/bash
API="https://sitio.local/wp-json"
AUTH="admin:xxxx xxxx xxxx xxxx"

# 1. Crear post de landing
LANDING_ID=$(curl -s -X POST "$API/wp/v2/flavor_landing" \
  -u "$AUTH" \
  -H "Content-Type: application/json" \
  -d '{"title": "Mi Landing", "status": "publish"}' \
  | jq '.id')

# 2. Añadir contenido VBP
curl -X POST "$API/flavor-vbp/v1/documents/$LANDING_ID" \
  -u "$AUTH" \
  -H "Content-Type: application/json" \
  -d @landing-content.json

echo "Landing creada: $LANDING_ID"
```

### Obtener Schema de Bloques

```bash
curl -s "$API/flavor-vbp/v1/blocks/schema" | jq '.blocks | keys'
```

### Validar y Crear Sitio

```bash
# Validar
VALID=$(curl -s -X POST "$API/flavor-site-builder/v1/site/validate" \
  -u "$AUTH" \
  -H "Content-Type: application/json" \
  -d '{"template": "grupo_consumo"}' \
  | jq '.valid')

if [ "$VALID" = "true" ]; then
  # Crear
  curl -X POST "$API/flavor-site-builder/v1/site/create" \
    -u "$AUTH" \
    -H "Content-Type: application/json" \
    -d '{"template": "grupo_consumo", "demo_data": true}'
fi
```
