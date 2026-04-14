# Ejemplos de Uso de la API

## Obtener API Key

```bash
cd /ruta/wordpress
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
```

## Verificar Estado

```bash
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/capabilities" \
  -H "X-VBP-Key: $API_KEY" | jq
```

## Crear Pagina con Preset

```bash
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mi Landing",
    "preset": "modern",
    "sections": ["hero", "features", "testimonials", "cta"],
    "set_as_homepage": true,
    "status": "publish"
  }'
```

## Crear Sitio Completo

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/site/create" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "name": "Mi Cooperativa",
    "modules": ["grupos_consumo", "socios", "eventos", "marketplace"],
    "create_pages": true,
    "create_menus": true,
    "configure_footer": true,
    "theme": "light"
  }'
```

## Crear Landing Completa

```bash
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Inicio",
    "slug": "inicio",
    "set_as_homepage": true,
    "content": {
      "blocks": [
        {
          "type": "section",
          "props": {
            "className": "hero-section",
            "background": {"type": "gradient", "from": "#2d5016", "to": "#4a7c23"}
          },
          "children": [
            {
              "type": "container",
              "children": [
                {"type": "heading", "props": {"level": 1, "text": "Cooperativa Verde", "align": "center", "color": "#fff"}},
                {"type": "text", "props": {"content": "Consumo responsable", "align": "center", "color": "#fff"}},
                {"type": "button", "props": {"text": "Unete", "url": "/hazte-socio", "style": "primary"}}
              ]
            }
          ]
        }
      ]
    }
  }'
```

## Crear Menu

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/menu" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "location": "primary",
    "items": [
      {"title": "Inicio", "url": "/", "type": "custom"},
      {"title": "Productos", "url": "/productos", "type": "custom"},
      {"title": "Contacto", "url": "/contacto", "type": "custom"}
    ]
  }'
```

## Operaciones Batch

```bash
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/batch" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "operations": [
      {"id": "page1", "method": "POST", "path": "/claude/pages", "body": {"title": "Pagina 1"}},
      {"id": "page2", "method": "POST", "path": "/claude/pages", "body": {"title": "Pagina 2"}}
    ],
    "stop_on_error": false
  }'
```

## Configurar App Movil

```bash
# Branding
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/branding" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "Mi Cooperativa",
    "app_id": "com.micooperativa.app",
    "logo_url": "https://sitio.com/logo.png"
  }'

# Tema
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/theme" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"preset": "emerald-green"}'

# Modulos
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/modules" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"modules": ["eventos", "socios", "marketplace"]}'
```

## Traduccion (Multilingual)

```bash
# Traducir texto
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/translate" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"text": "Bienvenidos", "from_lang": "es", "to_lang": "eu"}'

# Traducir post completo
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/translate/post/123" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"to_lang": "eu"}'
```
