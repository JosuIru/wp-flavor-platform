# Flavor Platform - Instrucciones OBLIGATORIAS para Claude Code

## REGLAS CRÍTICAS - LEER PRIMERO

Cuando trabajes con sitios que tienen Flavor Platform, DEBES seguir estas reglas SIN EXCEPCIONES:

### 🚫 PROHIBIDO

1. **NUNCA crear páginas con HTML plano ni Gutenberg**
   ```php
   // ❌ PROHIBIDO - No usar wp post create para contenido
   wp post create --post_content="<p>Bienvenido</p>"

   // ❌ PROHIBIDO - No usar bloques Gutenberg
   wp post create --post_content="<!-- wp:paragraph --><p>Texto</p><!-- /wp:paragraph -->"

   // ❌ PROHIBIDO - No crear posts tipo 'page' para landings
   wp post create --post_type=page --post_title="Mi Página"
   ```

2. **NUNCA usar Gutenberg ni el editor clásico** - SIEMPRE usar Visual Builder Pro API

3. **NUNCA activar más de 15-20 módulos** sin justificación del usuario

4. **NUNCA configurar un tema que no esté instalado**

5. **NUNCA crear menús sin asignarlos a ubicaciones**

6. **NUNCA omitir la configuración del footer**

### ✅ OBLIGATORIO

1. **SIEMPRE ejecutar validación primero**
2. **SIEMPRE usar Visual Builder Pro para páginas con diseño**
3. **SIEMPRE verificar que el tema existe antes de activarlo**
4. **SIEMPRE asignar menús a ubicaciones (primary, footer, mobile)**
5. **SIEMPRE configurar footer con widgets**

---

## PASO 0: Validación Pre-vuelo (OBLIGATORIO)

ANTES de hacer cualquier cambio, ejecuta el validador:

```bash
# Ejecutar validador
bash /ruta/plugin/tools/validate-site.sh "http://SITIO" "/ruta/wordpress"

# O manualmente:
cd /ruta/wordpress

# 1. Verificar plugin activo
wp plugin is-active flavor-platform || echo "ERROR: Plugin no activo"

# 2. Verificar tema EXISTE (no solo configurado)
ls wp-content/themes/flavor-starter || echo "ERROR: Tema no instalado"

# 3. Verificar tema activo
wp option get template  # Debe ser "flavor-starter"

# 4. Obtener API key y verificar API
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/system/health" \
  -H "X-VBP-Key: $API_KEY"
```

**SI LA VALIDACIÓN FALLA**: No continúes. Primero corrige los errores.

---

## PASO 0.5: Discovery de Elementos (OBLIGATORIO antes de componer páginas)

> **CRÍTICO**: ANTES de crear cualquier página con VBP, DEBES consultar qué elementos están realmente disponibles. NO supongas qué bloques existen.

### Script de inventario rápido

```bash
# Ejecutar SIEMPRE antes de componer páginas
bash tools/vbp-inventory.sh "http://SITIO"
```

Este script muestra todos los bloques, secciones, módulos y presets disponibles.

### Consultas manuales de discovery

> **NOTA**: Primero obtén la API key dinámicamente:
> ```bash
> cd /ruta/wordpress
> API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
> ```

#### A. Schema completo (fuente de verdad)
```bash
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/schema" \
  -H "X-VBP-Key: $API_KEY" > /tmp/vbp-schema.json

# Ver bloques por categoría
cat /tmp/vbp-schema.json | jq '.blocks | group_by(.category)'

# Consultar un bloque específico con sus props
cat /tmp/vbp-schema.json | jq '.blocks[] | select(.id=="hero-banner")'
```

#### B. Bloques disponibles
```bash
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/blocks" \
  -H "X-VBP-Key: $API_KEY" | jq '.blocks[] | {id, name, category}'
```

#### C. Tipos de sección
```bash
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/section-types" \
  -H "X-VBP-Key: $API_KEY"
```

#### D. Presets de diseño
```bash
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/design-presets" \
  -H "X-VBP-Key: $API_KEY"
```

#### E. Módulos activos (¡IMPORTANTE! solo usar los activos)
```bash
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/modules" \
  -H "X-VBP-Key: $API_KEY" | jq '.[] | select(.active==true) | .id'
```

### REGLAS DE DISCOVERY

1. **NUNCA uses un bloque que no aparezca en `/claude/blocks`**
2. **NUNCA references un módulo que no esté ACTIVO**
3. **NUNCA inventes presets de diseño** - usa solo los existentes
4. **Si algo no existe, PREGUNTA** antes de improvisar con HTML/Gutenberg

### Flujo obligatorio para componer páginas

```
1. Ejecutar vbp-inventory.sh
2. Listar qué bloques/módulos vas a usar
3. Verificar que TODOS existen en el inventario
4. Solo entonces componer la página
5. Si falta algo, preguntar al usuario
```

---

## Configuración de API Key (IMPORTANTE)

La API de VBP usa una key de autenticación configurable. **NO usar la key legacy en producción.**

### Obtener la API Key actual

```bash
# Via WP-CLI
wp eval "echo flavor_get_vbp_api_key();"

# O via API (requiere estar autenticado en WP)
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/status" \
  -H "Cookie: wordpress_logged_in_xxx=..."
```

### Regenerar API Key (recomendado en producción)

```bash
wp eval "echo flavor_regenerate_vbp_api_key();"
```

### Usar API Key en requests

```bash
# Obtener la key
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();" 2>/dev/null)

# Usar en requests
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: $API_KEY"
```

### Modo desarrollo (solo local)

La key legacy ya no debe usarse como flujo normal. En local, la práctica recomendada es regenerar una key propia y limitar los scopes desde Ajustes > VBP o con filtros de entorno.

```php
// wp-config.php o mu-plugin
add_filter( 'flavor_vbp_automation_enabled', function( $enabled, $scope ) {
    if ( wp_get_environment_type() !== 'local' ) {
        return $enabled;
    }

    return $scope !== 'site_builder' ? $enabled : false;
}, 10, 2 );
```

> **ADVERTENCIA**: No documentar ni reutilizar keys compartidas entre instalaciones.

---

## Estructura de APIs

### Site Builder API
Base: `/wp-json/flavor-site-builder/v1/`
Header: `X-VBP-Key: <API_KEY>` (ver sección anterior)

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/system/health` | GET | Verificar estado |
| `/profiles` | GET | Listar perfiles de app |
| `/templates` | GET | Listar plantillas |
| `/themes` | GET | Listar temas visuales |
| `/modules` | GET | Listar módulos |
| `/site/validate` | POST | Validar configuración |
| `/site/create` | POST | Crear sitio completo |
| `/site/status` | GET | Estado actual del sitio |
| `/modules/activate` | POST | Activar módulos |
| `/pages/create-for-modules` | POST | Crear páginas |
| `/menu` | POST | Crear/asignar menú |
| `/profile/set` | POST | Establecer perfil |
| `/theme/apply` | POST | Aplicar tema |
| `/design/options` | GET | Opciones de diseño |
| `/demo-data/import` | POST | Importar datos demo |

### Visual Builder Pro API (Claude)
Base: `/wp-json/flavor-vbp/v1/claude/`
Header: `X-VBP-Key: <API_KEY>` (ver sección "Configuración de API Key")

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/claude/status` | GET | Estado de VBP |
| `/claude/capabilities` | GET | Capacidades disponibles |
| `/claude/blocks` | GET | Listar bloques |
| `/claude/schema` | GET | Esquema completo |
| `/claude/pages` | GET | Listar páginas VBP |
| `/claude/pages` | POST | Crear página |
| `/claude/pages/{id}` | PUT | Actualizar página |
| `/claude/pages/{id}/publish` | POST | Publicar página |
| `/claude/pages/styled` | POST | Crear página con estilos |
| `/claude/templates` | GET | Plantillas VBP |
| `/claude/section-types` | GET | Tipos de sección |
| `/claude/design-presets` | GET | Presets de diseño |
| `/claude/widgets` | GET | Widgets disponibles |

---

## Flujo de Trabajo Correcto

### 1. Validar Entorno

```bash
# Verificar todo antes de empezar
cd /ruta/wordpress

# Plugin activo
wp plugin is-active flavor-platform && echo "OK: Plugin"

# Tema instalado Y activo
[ -d "wp-content/themes/flavor-starter" ] && echo "OK: Tema instalado"
[ "$(wp option get template)" = "flavor-starter" ] && echo "OK: Tema activo"

# API responde
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/system/health" \
  -H "X-VBP-Key: $API_KEY" | grep -q "ok" && echo "OK: API"
```

### 2. Si el Tema No Está Instalado

```bash
# Opción A: Copiar tema
cp -r /sitio-desarrollo/wp-content/themes/flavor-starter \
      /sitio-destino/wp-content/themes/

# Opción B: Enlace simbólico (desarrollo local)
ln -s /sitio-desarrollo/wp-content/themes/flavor-starter \
      /sitio-destino/wp-content/themes/flavor-starter

# Activar tema
cd /sitio-destino
wp theme activate flavor-starter
```

### 3. Validar Configuración Antes de Aplicar

```bash
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/site/validate" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "grupos_consumo",
    "modules": ["grupos_consumo", "socios", "eventos"]
  }'
```

**Si devuelve errores**: No continúes hasta resolverlos.

### 4. Crear Sitio con API Completa

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

### 5. Crear Landing con Visual Builder Pro

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
                {"type": "text", "props": {"content": "Consumo responsable y sostenible", "align": "center", "color": "#fff"}},
                {"type": "button", "props": {"text": "Únete ahora", "url": "/hazte-socio", "style": "primary", "size": "large"}}
              ]
            }
          ]
        },
        {
          "type": "section",
          "props": {"className": "features-section", "padding": "60px 0"},
          "children": [
            {"type": "heading", "props": {"level": 2, "text": "¿Por qué unirte?", "align": "center"}},
            {
              "type": "columns",
              "props": {"columns": 3, "gap": "30px"},
              "children": [
                {"type": "feature-card", "props": {"icon": "leaf", "title": "Ecológico", "text": "Productos 100% ecológicos"}},
                {"type": "feature-card", "props": {"icon": "users", "title": "Comunidad", "text": "Forma parte de algo grande"}},
                {"type": "feature-card", "props": {"icon": "euro", "title": "Ahorra", "text": "Precios justos para todos"}}
              ]
            }
          ]
        },
        {
          "type": "section",
          "props": {"className": "products-section"},
          "children": [
            {"type": "heading", "props": {"level": 2, "text": "Productos destacados"}},
            {"type": "module-shortcode", "props": {"module": "marketplace", "view": "featured", "limit": 6}}
          ]
        }
      ]
    }
  }'
```

### 6. Configurar Menú Principal

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
      {"title": "Productores", "url": "/productores", "type": "custom"},
      {"title": "Eventos", "url": "/eventos", "type": "custom"},
      {"title": "Comunidad", "url": "/comunidad", "type": "custom",
        "children": [
          {"title": "Foros", "url": "/foros"},
          {"title": "Socios", "url": "/socios"}
        ]
      },
      {"title": "Contacto", "url": "/contacto", "type": "custom"}
    ]
  }'
```

### 7. Configurar Footer y Widgets

El footer se configura mediante widgets de WordPress y opciones de tema:

```bash
# Crear menú de footer
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/menu" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Footer",
    "location": "footer",
    "items": [
      {"title": "Privacidad", "url": "/privacidad"},
      {"title": "Términos", "url": "/terminos"},
      {"title": "Contacto", "url": "/contacto"}
    ]
  }'

# Configurar colores de footer via tema
curl -X POST "http://SITIO/wp-json/flavor-site-builder/v1/theme/apply" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "footer_bg_color": "#1f2937",
    "footer_text_color": "#ffffff"
  }'

# Añadir widgets al footer (via WP-CLI)
cd /ruta/wordpress

# Widget de texto
wp widget add text footer-widgets 1 --title="Contacto" \
  --text="<p>Email: info@cooperativa.local</p><p>Tel: 944 123 456</p>"

# Widget de menú
wp widget add nav_menu footer-widgets 2 --title="Enlaces" --nav_menu=footer-menu

# Widget de redes sociales (si existe)
wp widget add flavor_social footer-widgets 3 --title="Síguenos"
```

---

## Páginas Funcionales vs Páginas de Diseño

### Páginas Funcionales (usar shortcodes)

Para páginas que muestran funcionalidad de módulos:

```bash
# Crear página con shortcode de módulo
wp post create \
  --post_type=page \
  --post_title="Mi Portal" \
  --post_content='[flavor_unified_dashboard]' \
  --post_status=publish

# Página de módulo específico
wp post create \
  --post_type=page \
  --post_title="Productos" \
  --post_content='[flavor_module module="marketplace" view="grid"]' \
  --post_status=publish
```

### Páginas de Diseño (OBLIGATORIO usar VBP)

Para landings, páginas informativas, home:

```bash
# SIEMPRE usar la API de VBP Claude
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mi Página",
    "slug": "mi-pagina",
    "content": {
      "blocks": [...]
    }
  }'

# O para página simple
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mi Página",
    "blocks": [...]
  }'
```

---

## Plantillas Disponibles

| ID | Nombre | Módulos |
|----|--------|---------|
| `grupos_consumo` | Grupo de Consumo | grupos-consumo, socios, eventos, marketplace |
| `comunidad` | Comunidad Vecinal | comunidades, participacion, eventos, foros |
| `asociacion` | Asociación/ONG | socios, eventos, transparencia, foros |
| `coworking` | Espacio Coworking | reservas, espacios-comunes, eventos |
| `centro_cultural` | Centro Cultural | eventos, talleres, biblioteca, multimedia |
| `cooperativa` | Cooperativa Trabajo | socios, transparencia, presupuestos-participativos |
| `barrio` | Gestión de Barrio | incidencias, avisos-municipales, participacion |

---

## Addon: Flavor Multilingual

Si el sitio requiere soporte multiidioma, usar el addon Flavor Multilingual.

### Verificar si está activo

```bash
# Via WP-CLI
wp plugin is-active flavor-multilingual || wp option get flavor_multilingual_settings

# Via API
curl -s "http://SITIO/wp-json/flavor-multilingual/v1/languages" \
  -H "X-VBP-Key: $API_KEY"
```

### API de Traducción

Base: `/wp-json/flavor-multilingual/v1/`

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/languages` | GET | Listar idiomas activos |
| `/languages/{code}` | GET | Obtener idioma específico |
| `/current-language` | GET | Idioma actual del usuario |
| `/posts/{id}/translations` | GET | Traducciones de un post |
| `/posts/{id}/translations` | POST | Guardar traducción |
| `/posts/{id}/translations/{lang}` | PUT | Actualizar traducción |
| `/translate` | POST | Traducir texto con IA |
| `/translate/post/{id}` | POST | Traducir post completo |
| `/strings` | GET/POST | Gestionar strings traducibles |
| `/stats` | GET | Estadísticas de traducción |

### Idiomas Soportados

- **España**: es (Español), eu (Euskara), ca (Català), gl (Galego), an (Aragonés), ast (Asturianu), oc (Occitan/Aranés)
- **Europa**: en, fr, de, it, pt, cy (Welsh), ga (Irish), gd (Gaelic), br (Breton), lb (Luxembourgish)
- **Otros**: zh (中文), ja (日本語), ar (العربية - RTL)

### Configurar Idiomas del Sitio

```bash
# Activar idiomas
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/languages/activate" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"languages": ["es", "eu", "en"]}'

# Establecer idioma por defecto
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/settings" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"default_language": "es", "url_mode": "directory"}'
```

### Traducir Contenido con IA

```bash
# Traducir texto libre
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/translate" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Bienvenidos a nuestra cooperativa",
    "from_lang": "es",
    "to_lang": "eu"
  }'

# Traducir post completo
curl -X POST "http://SITIO/wp-json/flavor-multilingual/v1/translate/post/123" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"to_lang": "eu"}'
```

### Crear Página Multiidioma con VBP

```bash
# 1. Crear página en idioma base
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"title": "Inicio", "slug": "inicio", "content": {...}}'

# 2. Traducir a otros idiomas
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/123/translate" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{"target_lang": "eu"}'

# 3. O traducir a todos los idiomas activos
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/123/translate-all" \
  -H "X-VBP-Key: $API_KEY"
```

### Flujo Multiidioma Completo

1. **Crear sitio** en idioma base (normalmente español)
2. **Activar addon** multilingual
3. **Configurar idiomas** activos
4. **Crear contenido** en idioma base
5. **Traducir** páginas y posts con IA
6. **Verificar** traducciones automáticas
7. **Configurar** selector de idioma en menú/widget

---

## Checklist Final

Antes de dar por terminado, verifica:

```bash
cd /ruta/wordpress

# 1. Tema correcto
[ "$(wp option get template)" = "flavor-starter" ] && echo "✓ Tema"

# 2. Módulos activos (no más de 20)
MODULOS=$(wp option get flavor_active_modules --format=json 2>/dev/null | jq length)
[ "$MODULOS" -lt 20 ] && echo "✓ Módulos: $MODULOS"

# 3. Páginas con VBP
VBP=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_flavor_vbp_data'" --skip-column-names)
[ "$VBP" -gt 0 ] && echo "✓ Páginas VBP: $VBP"

# 4. Menús asignados
wp menu location list --format=table | grep -v "^$" && echo "✓ Menús"

# 5. Homepage configurada
HOME=$(wp option get page_on_front)
[ "$HOME" -gt 0 ] && echo "✓ Homepage: $HOME"

# 6. Footer widgets
WIDGETS=$(wp widget list footer-widgets --format=count 2>/dev/null || echo "0")
[ "$WIDGETS" -gt 0 ] && echo "✓ Footer widgets: $WIDGETS"

# 7. Multiidioma (si aplica)
if wp option get flavor_multilingual_settings &>/dev/null; then
    LANGS=$(curl -s "http://SITIO/wp-json/flavor-multilingual/v1/languages" | jq length)
    echo "✓ Idiomas activos: $LANGS"
fi
```

### Resumen de Verificación

| Componente | Comando | Esperado |
|------------|---------|----------|
| Tema | `wp option get template` | `flavor-starter` |
| Módulos | `wp option get flavor_active_modules` | < 20 activos |
| VBP | Query `_flavor_vbp_data` | > 0 páginas |
| Menús | `wp menu location list` | primary, footer asignados |
| Homepage | `wp option get page_on_front` | ID > 0 |
| Multilingual | API `/languages` | Idiomas configurados |

---

## Configuración de Apps Móviles (APKs)

Para configurar las apps Flutter según los módulos activados en el sitio.

> **IMPORTANTE**: Antes de configurar cualquier APK, ejecutar `bash tools/apk-inventory.sh`
> Ver documentación completa en `CLAUDE-APK.md`

### Regla de Habilitación de Módulos

| Soporte | Acción |
|---------|--------|
| **3/3** (WordPress + Flutter + API) | Habilitar automáticamente |
| **2/3** (soporte parcial) | **Pedir permiso al usuario** explicando qué falta y el fallback disponible |
| **1/3 o menos** | Advertir y no recomendar |

**Ejemplo de solicitud de permiso:**
> "El módulo `chat-grupos` está activo en WordPress pero no tiene template Flutter nativo.
> ¿Quieres que lo habilite usando WebView como fallback? (será más lento pero funcional)"

### API de Configuración de Apps

Base: `/wp-json/flavor-vbp/v1/app/`
Header: `X-VBP-Key: <API_KEY>`

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/app/config` | GET | Obtener configuración actual |
| `/app/config` | POST | Actualizar configuración |
| `/app/branding` | GET/POST | Logo, nombre, colores |
| `/app/theme` | GET/POST | Tema de colores |
| `/app/theme-presets` | GET | Presets disponibles |
| `/app/modules` | GET | Módulos disponibles |
| `/app/modules` | POST | Activar módulos |

### Módulos Disponibles para Apps

| Categoría | Módulos |
|-----------|---------|
| Comunidad | eventos, foros, socios, comunidades |
| Economía | marketplace, grupos-consumo, banco-tiempo |
| Reservas | reservas, espacios-comunes, bicicletas, parkings |
| Formación | cursos, talleres, biblioteca |
| Participación | encuestas, presupuestos-participativos, campanias |
| Social | red-social, chat-interno |
| Movilidad | carpooling |
| Gestión | incidencias, tramites, transparencia |
| Cultura | kulturaka, multimedia, radio, podcast |

### Configurar App Completa

```bash
# 1. Obtener configuración actual
curl -s "http://SITIO/wp-json/flavor-vbp/v1/app/config" \
  -H "X-VBP-Key: $API_KEY"

# 2. Configurar branding
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/branding" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "Mi Cooperativa",
    "app_id": "com.micooperativa.app",
    "logo_url": "https://sitio.com/logo.png",
    "splash_color": "#2d5016"
  }'

# 3. Seleccionar tema de colores
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/theme" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "preset": "emerald-green",
    "custom_colors": {
      "primary": "#2d5016",
      "secondary": "#4a7c23"
    }
  }'

# 4. Activar módulos para la app
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/modules" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["eventos", "socios", "marketplace", "grupos-consumo", "foros"]
  }'
```

### Presets de Tema Disponibles

| Preset | Descripción | Ideal para |
|--------|-------------|------------|
| `modern-blue` | Azul moderno | General |
| `emerald-green` | Verde esmeralda | Ecología, cooperativas |
| `purple-violet` | Violeta | Cultura, creatividad |
| `warm-orange` | Naranja cálido | Comunidad, social |
| `corporate` | Corporativo | Instituciones |

### Configuración de Navegación

```bash
# Configurar tipo de navegación
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/config" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "navigation_style": "bottom_tabs",
    "tabs": [
      {"id": "home", "label": "Inicio", "icon": "home"},
      {"id": "eventos", "label": "Eventos", "icon": "calendar"},
      {"id": "marketplace", "label": "Tienda", "icon": "shopping_bag"},
      {"id": "comunidad", "label": "Comunidad", "icon": "people"},
      {"id": "perfil", "label": "Perfil", "icon": "person"}
    ]
  }'
```

### Página de Información (Info/About)

```bash
# Configurar página de información
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/config" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "info_page": {
      "show_about": true,
      "show_contact": true,
      "show_social": true,
      "show_legal": true,
      "sections": [
        {"type": "about", "title": "Quiénes somos", "content": "..."},
        {"type": "contact", "email": "info@coop.local", "phone": "944123456"},
        {"type": "social", "twitter": "@coop", "instagram": "@coop"},
        {"type": "links", "items": [
          {"title": "Términos", "url": "/terminos"},
          {"title": "Privacidad", "url": "/privacidad"}
        ]}
      ]
    }
  }'
```

### Flujo Completo de Configuración de App

1. **Verificar módulos activos en el sitio**
   ```bash
   curl -s "http://SITIO/wp-json/flavor-site-builder/v1/modules" -H "X-VBP-Key: $API_KEY"
   ```

2. **Obtener menús del sitio**
   ```bash
   wp menu list --format=json
   ```

3. **Configurar app con módulos correspondientes**
   - Solo activar módulos que estén activos en el sitio
   - Mapear menús del sitio a navegación de la app

4. **Configurar branding igual que el sitio**
   - Usar mismo logo
   - Usar mismos colores del tema

5. **Verificar configuración**
   ```bash
   curl -s "http://SITIO/wp-json/flavor-vbp/v1/app/config" -H "X-VBP-Key: $API_KEY" | jq
   ```

### Sincronizar App con Sitio Web

IMPORTANTE: La app debe reflejar la configuración del sitio:

| Sitio Web | App Móvil |
|-----------|-----------|
| Módulos activos | `app/modules` |
| Tema/colores | `app/theme` |
| Logo | `app/branding` |
| Menú principal | `navigation.tabs` |
| Páginas info | `info_page.sections` |

```bash
# Comando para sincronizar todo
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/app/sync-from-site" \
  -H "X-VBP-Key: $API_KEY"
```

---

## Documentación Adicional

### Guías Especializadas
- `CLAUDE-APK.md` - **Configuración de APKs móviles (OBLIGATORIO para apps)**
- `docs/api/CLAUDE-API-GUIDE.md` - Guía completa APIs
- `docs/api/WORKFLOW-CREAR-SITIO.md` - Tutorial detallado
- `docs/api/ENDPOINTS-REFERENCE.md` - Referencia endpoints
- `docs/modulos/` - Documentación por módulo

### Scripts de Discovery (OBLIGATORIOS)
| Script | Uso | Ejecutar antes de |
|--------|-----|-------------------|
| `tools/validate-site.sh` | Validar entorno WordPress | Cualquier operación |
| `tools/vbp-inventory.sh` | Inventario de bloques VBP | Componer páginas |
| `tools/apk-inventory.sh` | Inventario 3 niveles APK | Configurar apps móviles |
| `tools/full-inventory.sh` | **Inventario completo** (3 en 1) | Proyectos nuevos |

### Pre-commit Hook
Para validar módulos antes de commit:
```bash
cp tools/hooks/pre-commit-modules .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### API de Compatibilidad de Módulos
Base: `/wp-json/flavor-platform/v1/`

| Endpoint | Descripción |
|----------|-------------|
| `/modules/compatibility` | Matriz completa 3 niveles |
| `/modules/{id}/check` | Verificar módulo específico |
| `/modules/supported` | Lista de módulos con soporte completo |
| `/diagnostics` | Diagnóstico completo del sistema |

### Regla de Discovery
**NUNCA** asumas qué elementos existen. Ejecuta el script correspondiente:
```bash
# Inventario completo (recomendado para proyectos nuevos)
bash tools/full-inventory.sh "http://SITIO" "." "mobile-apps"

# O inventarios individuales:
bash tools/vbp-inventory.sh "http://SITIO"      # Solo páginas web
bash tools/apk-inventory.sh "http://SITIO" "mobile-apps"  # Solo APKs
```

---

## Plantillas Comunitarias Disponibles

### Plantillas VBP para Comunidades

| Plantilla | Descripción | Secciones Incluidas |
|-----------|-------------|---------------------|
| `crowdfunding` | Financiación colectiva | hero, module_crowdfunding, como_funciona, impacto, testimonials, faq, donacion |
| `cooperativa` | Cooperativas de trabajo/consumo | hero, valores, features, module_socios, module_transparencia, team, cta |
| `asociacion-completa` | Asociaciones y ONGs | hero, features, impacto, proyectos_destacados, module_socios, team, testimonials, faq, cta |
| `barrio-vecinal` | Comunidades de vecinos | hero, features, module_participacion, module_eventos, mapa_comunidad, cta |
| `espacio-cultural` | Centros culturales/coworking | hero, features, module_reservas, module_eventos, gallery, pricing, contact |
| `economia-social` | Economía solidaria | hero, valores, module_banco_tiempo, module_marketplace, impacto, testimonials, cta |
| `captacion-socios` | Landing para captar socios | hero, features, como_funciona, testimonials, pricing, faq, cta |
| `transparencia-municipal` | Portal de transparencia | hero, module_transparencia, module_participacion, stats, faq, contact |

### Presets de Diseño Comunitarios

| Preset | Descripción | Uso Ideal |
|--------|-------------|-----------|
| `community` | Cálido y acogedor, violetas y naranjas | Comunidades, asociaciones |
| `cooperative` | Rojo solidario y azul profesional | Cooperativas de trabajo |
| `eco` | Verdes naturales y tierra | Ecología, sostenibilidad |
| `fundraising` | Verde y violeta, optimizado para donaciones | Crowdfunding, recaudación |
| `nature` | Verdes terrosos | Grupos de consumo, huertos |
| `modern` | Diseño moderno limpio | General |

### Crear Página de Crowdfunding

```bash
# ✅ CORRECTO: Usar VBP styled pages
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Apoya Nuestros Proyectos",
    "preset": "fundraising",
    "sections": ["hero", "module_crowdfunding", "como_funciona", "impacto", "donacion", "faq"],
    "context": {
      "topic": "Financiación Colectiva",
      "industry": "community"
    },
    "status": "publish"
  }'
```

### Secciones Comunitarias Disponibles

| Sección | Descripción |
|---------|-------------|
| `module_crowdfunding` | Grid de proyectos con barra de progreso |
| `module_socios` | Formulario de captación de socios |
| `module_participacion` | Propuestas y votaciones |
| `module_transparencia` | Presupuestos y actas |
| `module_banco_tiempo` | Intercambio de servicios |
| `module_reservas` | Calendario de reservas |
| `como_funciona` | Pasos numerados explicativos |
| `valores` | Valores y principios de la comunidad |
| `impacto` | Estadísticas de impacto social |
| `proyectos_destacados` | Grid de proyectos con progreso |
| `donacion` | CTA para donaciones con opciones |
| `mapa_comunidad` | Mapa interactivo de puntos |

---

## Recuerda: SIEMPRE VBP, NUNCA Gutenberg

Para crear cualquier página con diseño visual:

```bash
# ✅ CORRECTO: Visual Builder Pro
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"title": "...", "preset": "...", "sections": [...], "status": "publish"}'

# ❌ INCORRECTO: WordPress posts
wp post create --post_type=page --post_title="..." --post_content="..."
```

---

## Plantilla de Prompt Estructurado para Componer Páginas

Cuando el usuario pida crear una página, sigue este flujo estructurado:

### Paso 1: Definir contexto

```
TAREA: Componer página con Visual Builder Pro

CONTEXTO:
- Tipo: [landing captación / portal comunidad / página informativa]
- Público: [clínica, cooperativa, asociación, tienda...]
- Objetivo principal: [captar socios / mostrar servicios / informar / vender]
- Módulos requeridos: [marketplace, eventos, socios...]
```

### Paso 2: Discovery obligatorio (ANTES de escribir código)

```bash
# 1. Ejecutar inventario completo
bash tools/vbp-inventory.sh "http://SITIO"

# 2. O consultas específicas:
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/blocks" -H "X-VBP-Key: $API_KEY"
curl -s "http://SITIO/wp-json/flavor-site-builder/v1/modules" -H "X-VBP-Key: $API_KEY" | jq '.[] | select(.active==true)'
```

### Paso 3: Listar qué vas a usar

Antes de componer, declara explícitamente:

```
VOY A USAR:
- Preset de diseño: community (verificado en /design-presets)
- Secciones: hero, features, module_eventos (verificadas en /section-types)
- Bloques: heading, text, button, columns (verificados en /blocks)
- Módulos: eventos, socios (verificados como ACTIVOS)
```

### Paso 4: Si algo no existe, PREGUNTAR

```
⚠️ El bloque "video-gallery" no aparece en /claude/blocks.
¿Quieres que:
  A) Use un bloque alternativo existente (gallery, multimedia)
  B) Omita esa sección
  C) Otra solución
```

### Paso 5: Solo entonces, componer la página

```bash
curl -X POST "http://SITIO/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "...",
    "preset": "community",
    "sections": ["hero", "features", "module_eventos"],
    "status": "publish"
  }'
```

---

## Configuración de API Key

### Gestión Visual (Admin)

La API key se puede gestionar desde el panel de administración:
- **Menú**: Flavor Platform → Config. VBP
- **Acciones**: Ver, copiar, regenerar API key

### Obtener API Key programáticamente

```bash
# Via WP-CLI
wp eval "echo flavor_get_vbp_api_key();"

# Verificar que la key funciona
API_KEY=$(wp eval "echo flavor_get_vbp_api_key();")
curl -s "http://SITIO/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: $API_KEY"
```

### Funciones PHP disponibles

```php
// Obtener la API key actual
$api_key = flavor_get_vbp_api_key();

// Verificar si una key es válida
$es_valida = flavor_verify_vbp_api_key( $mi_key );

// Regenerar API key (admin only)
$nueva_key = flavor_regenerate_vbp_api_key();
```

### Configuración en wp-config.php

```php
// Desactivar automatización en producción salvo scopes permitidos
add_filter( 'flavor_vbp_automation_enabled', function( $enabled ) {
    return wp_get_environment_type() === 'local' ? $enabled : false;
} );
```

### Opciones de Configuración VBP

La página de configuración permite ajustar:

| Opción | Descripción |
|--------|-------------|
| **API Key** | Ver/copiar/regenerar la key de autenticación |
| **Automatización externa** | Activar o desactivar autenticación por `X-VBP-Key` |
| **Scopes permitidos** | Limitar qué grupos de endpoints aceptan la key |
| **Reemplazar Gutenberg** | Usar VBP en lugar de Gutenberg para pages/posts |
| **Historial de versiones** | Guardar versiones anteriores de páginas VBP |

---

## Herramientas de Diagnóstico

| Script | Descripción | Uso |
|--------|-------------|-----|
| `tools/validate-site.sh` | Validación pre-vuelo completa | `bash tools/validate-site.sh "http://SITIO" "/ruta/wp"` |
| `tools/vbp-inventory.sh` | Inventario de elementos VBP | `bash tools/vbp-inventory.sh "http://SITIO"` |

### Ejecutar ambos antes de cualquier tarea compleja:

```bash
# 1. Validar que el sitio está configurado correctamente
bash tools/validate-site.sh "http://sitio.local" "/ruta/wordpress"

# 2. Obtener inventario de elementos disponibles
bash tools/vbp-inventory.sh "http://sitio.local"

# 3. Solo entonces, proceder con la tarea
```
