# Workflow: Crear un Sitio Completo con las APIs

Este tutorial guía paso a paso cómo crear un sitio web completo usando las APIs de Flavor Platform.

## Requisitos Previos

- WordPress instalado con Flavor Platform activo
- API Key configurada en la instalación actual
- URL base del sitio (ejemplo: `https://mi-sitio.com`)

## Escenario de Ejemplo

Vamos a crear un sitio para una **Cooperativa de Consumo** con:
- Módulos: grupos-consumo, socios, eventos, foros
- Landing page personalizada
- Menú de navegación
- Configuración SEO
- Datos de demostración

---

```bash
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
```

---

## Paso 1: Explorar Plantillas Disponibles

Primero, veamos qué plantillas están disponibles:

```bash
curl -H "X-VBP-Key: $API_KEY" \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/templates
```

**Respuesta:**
```json
{
  "grupos_consumo": {
    "id": "grupos_consumo",
    "name": "Grupo de Consumo",
    "description": "Para cooperativas de consumo ecológico",
    "icon": "🥬",
    "modules": ["grupos-consumo", "socios", "eventos"],
    "pages": ["inicio", "productos", "productores", "socios"],
    "has_landing": true,
    "has_demo": true
  },
  "comunidad": { ... },
  "tienda": { ... }
}
```

---

## Paso 2: Obtener Configuración de la Plantilla

Obtenemos la configuración completa de la plantilla elegida:

```bash
curl -H "X-VBP-Key: $API_KEY" \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/config-template/grupos_consumo
```

**Respuesta:**
```json
{
  "template_id": "grupos_consumo",
  "name": "Grupo de Consumo",
  "modules": {
    "required": ["grupos-consumo"],
    "optional": ["socios", "eventos", "foros"],
    "suggested": ["marketplace", "banco-tiempo"]
  },
  "pages": {
    "inicio": { "title": "Inicio", "template": "landing" },
    "productos": { "title": "Productos", "shortcode": "[gc_productos]" },
    "productores": { "title": "Productores", "shortcode": "[gc_productores]" }
  },
  "menu": {
    "items": [
      { "title": "Inicio", "url": "/" },
      { "title": "Productos", "url": "/productos" },
      { "title": "Productores", "url": "/productores" }
    ]
  },
  "_create_command": {
    "endpoint": "/site/create",
    "method": "POST",
    "body": {
      "template": "grupos_consumo",
      "site_name": "Mi Sitio",
      "import_demo": true
    }
  }
}
```

---

## Paso 3: Opción A - Creación Automática (Recomendada)

La forma más rápida es usar el endpoint de creación completa:

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
    "create_pages": true,
    "modules": {
      "additional": ["foros", "banco-tiempo"]
    },
    "customization": {
      "primary_color": "#059669",
      "logo_url": "https://mi-sitio.com/wp-content/uploads/logo.png"
    }
  }' \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/site/create
```

**Respuesta:**
```json
{
  "success": true,
  "steps": {
    "profile": {
      "success": true,
      "profile": "grupos_consumo"
    },
    "modules": {
      "activated": ["grupos-consumo", "socios", "eventos", "foros", "banco-tiempo"],
      "failed": []
    },
    "pages": {
      "created": [
        { "id": 101, "title": "Inicio", "url": "/inicio" },
        { "id": 102, "title": "Productos", "url": "/productos" },
        { "id": 103, "title": "Productores", "url": "/productores" },
        { "id": 104, "title": "Socios", "url": "/socios" }
      ]
    },
    "menu": {
      "menu_id": 5,
      "items_created": 4,
      "location": "primary"
    },
    "theme": {
      "applied": "light-eco",
      "colors": {
        "primary": "#059669",
        "secondary": "#84cc16"
      }
    },
    "demo_data": {
      "grupos-consumo": { "productos": 25, "productores": 8, "ciclos": 3 },
      "socios": { "socios": 50 },
      "eventos": { "eventos": 10 }
    }
  },
  "site_url": "https://mi-sitio.com",
  "admin_url": "https://mi-sitio.com/wp-admin"
}
```

**¡Listo!** Con un solo comando hemos creado el sitio completo.

---

## Paso 3: Opción B - Creación Manual Paso a Paso

Si prefieres control total, sigue estos pasos individuales:

### 3.1 Activar Módulos

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["grupos-consumo", "socios", "eventos", "foros"]
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/modules/activate-batch
```

**Respuesta:**
```json
{
  "success": true,
  "activated": ["grupos-consumo", "socios", "eventos", "foros"],
  "already_active": [],
  "failed": [],
  "total_active": 4
}
```

### 3.2 Aplicar Tema de Diseño

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "theme": "light-eco",
    "customization": {
      "primary_color": "#059669",
      "secondary_color": "#84cc16",
      "font_family": "Inter"
    }
  }' \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/theme/apply
```

### 3.3 Crear Landing Page

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cooperativa Verde",
    "preset": "nature",
    "status": "publish",
    "sections": ["hero", "features", "products", "testimonials", "cta"],
    "context": {
      "business_name": "Cooperativa Verde",
      "tagline": "Consumo responsable y ecológico",
      "features": [
        { "icon": "leaf", "title": "100% Ecológico", "text": "Productos certificados" },
        { "icon": "users", "title": "Comunidad", "text": "+500 familias" },
        { "icon": "truck", "title": "Reparto semanal", "text": "A domicilio" }
      ],
      "cta_text": "Únete ahora",
      "cta_url": "/registro"
    }
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/claude/pages/styled
```

**Respuesta:**
```json
{
  "success": true,
  "page_id": 101,
  "title": "Cooperativa Verde",
  "url": "https://mi-sitio.com/cooperativa-verde/",
  "edit_url": "https://mi-sitio.com/wp-admin/post.php?post=101&action=edit",
  "elements_created": 5,
  "preset_applied": "nature"
}
```

### 3.4 Crear Páginas de Módulos

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["grupos-consumo", "socios", "eventos"],
    "publish": true
  }' \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/pages/create-for-modules
```

### 3.5 Crear Menú de Navegación

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "location": "primary",
    "items": [
      { "title": "Inicio", "url": "/", "icon": "home" },
      { "title": "Productos", "url": "/productos", "icon": "cart" },
      { "title": "Productores", "url": "/productores", "icon": "users" },
      { "title": "Eventos", "url": "/eventos", "icon": "calendar" },
      { "title": "Foros", "url": "/foros", "icon": "comments" },
      { "title": "Área Socios", "url": "/socios", "icon": "user" }
    ]
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/site/menus
```

### 3.6 Configurar Layout

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "menu": "mega-menu",
    "footer": "multi-column"
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/site/layouts/active
```

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "logo_url": "https://mi-sitio.com/wp-content/uploads/logo.png",
    "cta_text": "Únete",
    "cta_url": "/registro",
    "social_links": {
      "instagram": "https://instagram.com/cooperativaverde",
      "facebook": "https://facebook.com/cooperativaverde"
    },
    "contact_email": "hola@cooperativaverde.com",
    "contact_phone": "+34 91 123 4567"
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/site/layouts/settings
```

### 3.7 Configurar SEO

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cooperativa Verde - Consumo Ecológico en Madrid",
    "description": "Únete a nuestra cooperativa de consumo responsable. Productos locales, ecológicos y de temporada directos del productor.",
    "keywords": ["cooperativa consumo", "ecológico", "Madrid", "productos locales"],
    "robots": "index, follow"
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/seo/pages/101
```

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "LocalBusiness",
    "data": {
      "name": "Cooperativa Verde",
      "description": "Cooperativa de consumo ecológico",
      "@type": "Organization",
      "address": {
        "streetAddress": "Calle Verde 123",
        "addressLocality": "Madrid",
        "postalCode": "28001",
        "addressCountry": "ES"
      }
    }
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/seo/pages/101/schema
```

### 3.8 Importar Datos de Demostración

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["grupos-consumo", "socios", "eventos"],
    "count": {
      "grupos-consumo": { "productos": 30, "productores": 10 },
      "socios": { "socios": 50 },
      "eventos": { "eventos": 15 }
    }
  }' \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/demo-data/import
```

---

## Paso 4: Verificar Estado del Sitio

```bash
curl -H "X-VBP-Key: $API_KEY" \
  https://mi-sitio.com/wp-json/flavor-site-builder/v1/site/status
```

**Respuesta:**
```json
{
  "site_name": "Cooperativa Verde",
  "site_url": "https://mi-sitio.com",
  "profile": "grupos_consumo",
  "theme": {
    "active": "light-eco",
    "colors": {
      "primary": "#059669",
      "secondary": "#84cc16"
    }
  },
  "modules": {
    "active": ["grupos-consumo", "socios", "eventos", "foros"],
    "count": 4
  },
  "pages": {
    "total": 6,
    "published": 6
  },
  "menus": {
    "primary": "Menu Principal"
  },
  "layout": {
    "menu": "mega-menu",
    "footer": "multi-column"
  },
  "demo_data": {
    "imported": true,
    "records": {
      "productos": 30,
      "productores": 10,
      "socios": 50,
      "eventos": 15
    }
  }
}
```

---

## Paso 5: Personalización Adicional

### 5.1 Añadir más páginas visuales

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Sobre Nosotros",
    "design_preset": "nature",
    "status": "publish",
    "elements": [
      {
        "type": "hero",
        "data": {
          "title": "Nuestra Historia",
          "subtitle": "Desde 2015 promoviendo el consumo responsable",
          "background_image": "https://mi-sitio.com/wp-content/uploads/equipo.jpg"
        }
      },
      {
        "type": "text",
        "data": {
          "content": "Somos una cooperativa de consumo formada por familias comprometidas con la alimentación saludable y sostenible..."
        }
      },
      {
        "type": "team",
        "data": {
          "title": "Nuestro Equipo",
          "members": [
            { "name": "María García", "role": "Coordinadora", "photo": "/uploads/maria.jpg" },
            { "name": "Carlos López", "role": "Logística", "photo": "/uploads/carlos.jpg" }
          ]
        }
      }
    ]
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/claude/pages
```

### 5.2 Traducir página a otro idioma

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to_lang": "eu",
    "save": true
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/claude/pages/101/translate
```

### 5.3 Configurar App Móvil

```bash
curl -X POST \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "Cooperativa Verde",
    "app_id": "com.cooperativaverde.app",
    "logo_url": "https://mi-sitio.com/wp-content/uploads/icon.png",
    "theme_preset": "emerald-green",
    "modules": ["grupos-consumo", "eventos", "socios"]
  }' \
  https://mi-sitio.com/wp-json/flavor-vbp/v1/app/branding
```

---

## Resumen de Endpoints Usados

| Paso | Endpoint | Método | Propósito |
|------|----------|--------|-----------|
| 1 | `/flavor-site-builder/v1/templates` | GET | Ver plantillas |
| 2 | `/flavor-site-builder/v1/config-template/{id}` | GET | Obtener config |
| 3A | `/flavor-site-builder/v1/site/create` | POST | Creación automática |
| 3.1 | `/flavor-vbp/v1/modules/activate-batch` | POST | Activar módulos |
| 3.2 | `/flavor-site-builder/v1/theme/apply` | POST | Aplicar tema |
| 3.3 | `/flavor-vbp/v1/claude/pages/styled` | POST | Crear landing |
| 3.4 | `/flavor-site-builder/v1/pages/create-for-modules` | POST | Páginas de módulos |
| 3.5 | `/flavor-vbp/v1/site/menus` | POST | Crear menú |
| 3.6 | `/flavor-vbp/v1/site/layouts/active` | POST | Configurar layout |
| 3.7 | `/flavor-vbp/v1/seo/pages/{id}` | POST | Configurar SEO |
| 3.8 | `/flavor-site-builder/v1/demo-data/import` | POST | Importar demo |
| 4 | `/flavor-site-builder/v1/site/status` | GET | Verificar estado |

---

## Errores Comunes

### Error 403: API key inválida
```json
{ "code": "rest_forbidden", "message": "API key inválida" }
```
**Solución:** Verificar header `X-VBP-Key` o parámetro `api_key`.

### Error 400: Plantilla no encontrada
```json
{ "code": "invalid_template", "message": "Plantilla 'xxx' no existe" }
```
**Solución:** Usar `GET /templates` para ver plantillas válidas.

### Error 500: Módulo no puede activarse
```json
{ "code": "module_error", "message": "Dependencia faltante: socios" }
```
**Solución:** Activar primero las dependencias del módulo.

---

## Tips y Buenas Prácticas

1. **Usa la creación automática** (`/site/create`) siempre que sea posible
2. **Verifica el estado** después de cada operación importante
3. **Importa datos demo** para probar el sitio rápidamente
4. **Configura SEO** desde el principio para mejor posicionamiento
5. **Usa presets de diseño** para consistencia visual

---

## Siguiente Paso

Ver la referencia completa de endpoints en `ENDPOINTS-REFERENCE.md`.
