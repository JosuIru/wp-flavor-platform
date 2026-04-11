# API REST de Visual Builder Pro

Documentacion completa de los endpoints REST disponibles en VBP.

## Base URL

```
/wp-json/flavor-vbp/v1/
```

## Autenticacion

Todos los endpoints requieren autenticacion excepto los marcados como publicos.

### WordPress Nonce

```javascript
fetch(url, {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
});
```

### API Key (para automatizacion)

```bash
curl -H "X-VBP-Key: YOUR_API_KEY" https://sitio.com/wp-json/flavor-vbp/v1/...
```

---

## Documentos

### Obtener Documento

```http
GET /documents/{id}
```

**Parametros:**
- `id` (required): ID del post/pagina

**Respuesta:**
```json
{
    "success": true,
    "post_id": 123,
    "elements": [...],
    "settings": {
        "pageWidth": "1200px",
        "backgroundColor": "#ffffff"
    },
    "version": "2.3.0"
}
```

### Guardar Documento

```http
POST /documents/{id}
Content-Type: application/json
```

**Body:**
```json
{
    "elements": [...],
    "settings": {...}
}
```

**Respuesta:**
```json
{
    "success": true,
    "post_id": 123,
    "revision_id": 456,
    "saved_at": "2025-04-11T10:30:00Z"
}
```

### Obtener Revisiones

```http
GET /documents/{id}/revisions
```

**Respuesta:**
```json
{
    "success": true,
    "revisions": [
        {
            "id": 456,
            "date": "2025-04-11T10:30:00Z",
            "author": "admin",
            "author_id": 1
        }
    ]
}
```

### Restaurar Revision

```http
POST /documents/{id}/revisions/{revision_id}/restore
```

---

## Bloques

### Listar Bloques

```http
GET /blocks
```

**Respuesta:**
```json
{
    "success": true,
    "blocks": {
        "hero": {
            "name": "Hero Section",
            "category": "layout",
            "icon": "<svg>...</svg>",
            "variants": ["centered", "split", "video"]
        },
        "heading": {...}
    }
}
```

### Schema de Bloques

```http
GET /blocks/schema
```

**Respuesta:**
```json
{
    "success": true,
    "schema": {
        "hero": {
            "id": "hero",
            "name": "Hero Section",
            "fields": {
                "title": { "type": "text", "label": "Titulo" },
                "subtitle": { "type": "textarea", "label": "Subtitulo" },
                "cta_text": { "type": "text", "label": "Texto boton" }
            }
        }
    }
}
```

### Renderizar Elemento

```http
POST /render-element
Content-Type: application/json
```

**Body:**
```json
{
    "element": {
        "type": "hero",
        "data": {
            "title": "Bienvenido"
        }
    }
}
```

**Respuesta:**
```json
{
    "success": true,
    "html": "<section class=\"vbp-hero\">...</section>"
}
```

---

## Templates

### Listar Templates

```http
GET /templates
```

### Crear Template

```http
POST /templates
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Mi Template",
    "category": "landing",
    "elements": [...],
    "thumbnail": "base64..."
}
```

### Aplicar Template

```http
POST /documents/{id}/apply-template
Content-Type: application/json
```

**Body:**
```json
{
    "template_id": 5
}
```

### Eliminar Template

```http
DELETE /templates/{id}
```

---

## Simbolos

### Listar Simbolos

```http
GET /symbols
```

**Respuesta:**
```json
{
    "success": true,
    "symbols": [
        {
            "id": "sym_abc123",
            "name": "Header Principal",
            "elements": [...],
            "variants": [...],
            "usage_count": 15
        }
    ]
}
```

### Crear Simbolo

```http
POST /symbols
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Mi Simbolo",
    "elements": [...],
    "exposed_props": ["title", "image"]
}
```

### Actualizar Simbolo

```http
PUT /symbols/{id}
Content-Type: application/json
```

### Eliminar Simbolo

```http
DELETE /symbols/{id}
```

### Obtener Instancias

```http
GET /symbols/{id}/instances
```

---

## Branching

### Listar Ramas

```http
GET /branches/{post_id}
```

**Respuesta:**
```json
{
    "success": true,
    "branches": [
        {
            "id": 1,
            "branch_name": "main",
            "branch_slug": "main",
            "is_main": true,
            "is_active": true,
            "created_at": "2025-04-01T00:00:00Z"
        }
    ]
}
```

### Crear Rama

```http
POST /branches
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "name": "feature-header",
    "description": "Nuevo diseno de header",
    "from_branch": 1
}
```

### Checkout

```http
POST /branches/{branch_id}/checkout
```

### Merge

```http
POST /branches/merge
Content-Type: application/json
```

**Body:**
```json
{
    "source_branch": 2,
    "target_branch": 1,
    "resolutions": {
        "el_abc": "source"
    }
}
```

### Diff

```http
GET /branches/diff?branch_a=1&branch_b=2
```

**Respuesta:**
```json
{
    "success": true,
    "diff": {
        "added": ["el_new"],
        "removed": ["el_old"],
        "modified": {
            "el_123": {
                "branch_a": {...},
                "branch_b": {...}
            }
        }
    }
}
```

### Eliminar Rama

```http
DELETE /branches/{branch_id}
```

---

## Realtime Collaboration

### Estado de Sesion

```http
GET /realtime/status/{post_id}
```

**Respuesta:**
```json
{
    "success": true,
    "users": [
        {
            "id": 1,
            "name": "Admin",
            "color": "#3b82f6"
        }
    ],
    "locks": {
        "el_123": { "user_id": 1, "expires": "..." }
    }
}
```

### Unirse a Sesion

```http
POST /realtime/join
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123
}
```

### Salir de Sesion

```http
POST /realtime/leave
Content-Type: application/json
```

### Solicitar Bloqueo

```http
POST /realtime/lock
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "element_id": "el_789"
}
```

### Liberar Bloqueo

```http
POST /realtime/unlock
Content-Type: application/json
```

### Sincronizar Cambios

```http
POST /realtime/sync
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "changes": [
        { "type": "update", "element_id": "el_123", "data": {...} }
    ]
}
```

---

## AI Layout

### Estado

```http
GET /ai/layout/status
```

**Respuesta:**
```json
{
    "success": true,
    "aiAvailable": true,
    "fallbackEnabled": true,
    "provider": "openai"
}
```

### Generar Layout

```http
POST /ai/layout/generate
Content-Type: application/json
```

**Body:**
```json
{
    "prompt": "Hero section con titulo y CTA",
    "context": {
        "industry": "tech",
        "style": "modern"
    }
}
```

**Respuesta:**
```json
{
    "success": true,
    "blocks": [
        {
            "type": "section",
            "data": {...},
            "children": [...]
        }
    ]
}
```

### Auto-Spacing

```http
POST /ai/layout/auto-spacing
Content-Type: application/json
```

**Body:**
```json
{
    "elements": [...],
    "gridBase": 8
}
```

### Sugerir Colores

```http
POST /ai/layout/colors
Content-Type: application/json
```

**Body:**
```json
{
    "baseColor": "#3b82f6",
    "scheme": "complementary"
}
```

**Respuesta:**
```json
{
    "success": true,
    "palette": {
        "primary": "#3b82f6",
        "secondary": "#f6823b",
        "accent": "#82f63b"
    }
}
```

### Templates IA

```http
GET /ai/layout/templates
```

---

## Global Styles

### Listar Estilos

```http
GET /global-styles
```

### Listar Agrupados

```http
GET /global-styles?group=1
```

### Obtener Estilo

```http
GET /global-styles/{id}
```

### Crear Estilo

```http
POST /global-styles
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Button Primary",
    "category": "buttons",
    "properties": {
        "backgroundColor": "#3b82f6",
        "color": "#ffffff",
        "padding": "12px 24px"
    }
}
```

### Actualizar Estilo

```http
PUT /global-styles/{id}
Content-Type: application/json
```

### Eliminar Estilo

```http
DELETE /global-styles/{id}
```

### Exportar CSS

```http
GET /global-styles/export-css
```

---

## Assets

### Listar Assets

```http
GET /assets?type=images&page=1&per_page=24
```

### Subir Asset

```http
POST /assets
Content-Type: multipart/form-data
```

### Favoritos

```http
POST /assets/{id}/favorite
DELETE /assets/{id}/favorite
```

### Colecciones

```http
GET /assets/collections
POST /assets/collections
POST /assets/{id}/collection/{collection_id}
```

---

## Exportacion

### Exportar HTML

```http
POST /export/html
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "include_css": true,
    "minify": true
}
```

### Exportar React

```http
POST /export/react
Content-Type: application/json
```

### Exportar Vue

```http
POST /export/vue
Content-Type: application/json
```

---

## Animations

### Listar Animaciones

```http
GET /animations/{element_id}
```

**Respuesta:**
```json
{
    "success": true,
    "animations": [
        {
            "id": "anim_123",
            "name": "Fade In",
            "type": "basic",
            "trigger": "scroll",
            "keyframes": [...],
            "duration": "0.6s",
            "easing": "ease-out"
        }
    ]
}
```

### Crear Animacion

```http
POST /animations
Content-Type: application/json
```

**Body:**
```json
{
    "element_id": "el_123",
    "name": "Mi Animacion",
    "type": "basic",
    "trigger": "scroll",
    "keyframes": [
        { "offset": 0, "properties": { "opacity": 0 } },
        { "offset": 100, "properties": { "opacity": 1 } }
    ],
    "duration": "0.5s"
}
```

### Actualizar Animacion

```http
PUT /animations/{animation_id}
Content-Type: application/json
```

### Eliminar Animacion

```http
DELETE /animations/{animation_id}
```

### Animaciones de Scroll

```http
GET /animations/scroll/{element_id}
POST /animations/scroll
PUT /animations/scroll/{animation_id}
DELETE /animations/scroll/{animation_id}
```

### Animaciones Avanzadas (Stagger, Motion Path, Spring)

```http
POST /animations/advanced
Content-Type: application/json
```

**Body:**
```json
{
    "element_id": "el_123",
    "type": "stagger",
    "targets": ".child",
    "animation": { "opacity": [0, 1] },
    "stagger": { "each": 0.1, "from": "center" }
}
```

---

## 3D / WebGL

### Estado del Sistema 3D

```http
GET /3d/status
```

**Respuesta:**
```json
{
    "success": true,
    "webglAvailable": true,
    "maxTextureSize": 16384,
    "supportedFormats": ["gltf", "glb", "obj"]
}
```

### Listar Escenas

```http
GET /3d/scenes/{post_id}
```

### Crear Escena

```http
POST /3d/scenes
Content-Type: application/json
```

**Body:**
```json
{
    "element_id": "el_123",
    "camera": { "type": "perspective", "fov": 75 },
    "lighting": "studio",
    "objects": [...]
}
```

### Actualizar Escena

```http
PUT /3d/scenes/{scene_id}
Content-Type: application/json
```

### Agregar Objeto 3D

```http
POST /3d/scenes/{scene_id}/objects
Content-Type: application/json
```

**Body:**
```json
{
    "type": "model",
    "src": "/models/character.glb",
    "position": [0, 0, 0],
    "scale": 1
}
```

### Subir Modelo 3D

```http
POST /3d/models
Content-Type: multipart/form-data
```

### Listar Modelos

```http
GET /3d/models
```

---

## Variables y Logica

### Listar Variables

```http
GET /variables
GET /variables/{collection}
```

**Respuesta:**
```json
{
    "success": true,
    "variables": {
        "color.primary": {
            "type": "color",
            "value": "#3b82f6",
            "modes": {
                "light": "#3b82f6",
                "dark": "#60a5fa"
            }
        }
    }
}
```

### Crear Variable

```http
POST /variables
Content-Type: application/json
```

**Body:**
```json
{
    "name": "color.custom",
    "type": "color",
    "value": "#ff0000",
    "collection": "brand",
    "description": "Color personalizado"
}
```

### Actualizar Variable

```http
PUT /variables/{name}
Content-Type: application/json
```

### Eliminar Variable

```http
DELETE /variables/{name}
```

### Cambiar Modo

```http
POST /variables/mode
Content-Type: application/json
```

**Body:**
```json
{
    "collection": "brand",
    "mode": "dark"
}
```

### Importar Variables

```http
POST /variables/import
Content-Type: application/json
```

**Body:**
```json
{
    "format": "figma",
    "data": {...}
}
```

### Exportar Variables

```http
GET /variables/export?format=css
GET /variables/export?format=json
GET /variables/export?format=figma
```

---

## Design Tokens

### Listar Tokens

```http
GET /tokens
GET /tokens/{type}
```

### Crear Token

```http
POST /tokens
Content-Type: application/json
```

### Actualizar Token

```http
PUT /tokens/{name}
Content-Type: application/json
```

### Eliminar Token

```http
DELETE /tokens/{name}
```

### Generar CSS

```http
GET /tokens/css
GET /tokens/css?modes=true
```

### Importar desde Figma

```http
POST /tokens/import/figma
Content-Type: application/json
```

### Exportar para Figma

```http
GET /tokens/export/figma
```

---

## Plugins

### Listar Plugins

```http
GET /plugins
```

**Respuesta:**
```json
{
    "success": true,
    "plugins": [
        {
            "id": "mi-plugin",
            "name": "Mi Plugin",
            "version": "1.0.0",
            "active": true
        }
    ]
}
```

### Activar Plugin

```http
POST /plugins/{plugin_id}/activate
```

### Desactivar Plugin

```http
POST /plugins/{plugin_id}/deactivate
```

### Configuracion de Plugin

```http
GET /plugins/{plugin_id}/settings
PUT /plugins/{plugin_id}/settings
Content-Type: application/json
```

---

## Accesibilidad

### Auditoria

```http
POST /accessibility/audit
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "level": "AA"
}
```

**Respuesta:**
```json
{
    "success": true,
    "errors": [...],
    "warnings": [...],
    "passes": [...]
}
```

### Verificar Contraste

```http
POST /accessibility/contrast
Content-Type: application/json
```

**Body:**
```json
{
    "foreground": "#333333",
    "background": "#ffffff"
}
```

---

## Performance

### Metricas

```http
GET /performance/metrics/{post_id}
```

**Respuesta:**
```json
{
    "success": true,
    "metrics": {
        "domNodes": 2341,
        "renderTime": 12,
        "memoryUsage": 245000000
    }
}
```

### Reporte

```http
GET /performance/report/{post_id}
```

---

## Offline / Sync

### Estado de Sincronizacion

```http
GET /sync/status/{post_id}
```

### Sincronizar Cambios

```http
POST /sync
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "changes": [...]
}
```

### Resolver Conflicto

```http
POST /sync/resolve
Content-Type: application/json
```

**Body:**
```json
{
    "post_id": 123,
    "resolution": "local"
}
```

---

## Codigos de Estado

| Codigo | Descripcion |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 400 | Solicitud invalida |
| 401 | No autorizado |
| 403 | Prohibido |
| 404 | No encontrado |
| 500 | Error del servidor |

## Errores

```json
{
    "success": false,
    "code": "invalid_element",
    "message": "El elemento especificado no existe"
}
```

## Rate Limiting

- 100 requests por minuto por usuario
- 1000 requests por hora por usuario
- Header `X-RateLimit-Remaining` indica requests restantes
