# Migracion de Elementor a VBP

Guia paso a paso para migrar sitios de Elementor a Visual Builder Pro.

---

## Indice

1. [Antes de Empezar](#antes-de-empezar)
2. [Evaluacion del Sitio Actual](#evaluacion-del-sitio-actual)
3. [Preparacion del Entorno](#preparacion-del-entorno)
4. [Proceso de Migracion](#proceso-de-migracion)
5. [Recreacion de Layouts](#recreacion-de-layouts)
6. [Migracion de Contenido](#migracion-de-contenido)
7. [Widgets y Funcionalidades](#widgets-y-funcionalidades)
8. [Templates Globales](#templates-globales)
9. [Estilos y Theme Builder](#estilos-y-theme-builder)
10. [Verificacion y Testing](#verificacion-y-testing)
11. [Desactivacion de Elementor](#desactivacion-de-elementor)
12. [FAQ](#faq)

---

## Antes de Empezar

### Por que migrar de Elementor a VBP

| Aspecto | Elementor | VBP |
|---------|-----------|-----|
| Performance (LCP) | 4-6s | 1.5-2s |
| HTML output | 85KB+ | 15KB |
| CSS output | 450KB+ | 45KB |
| Core Web Vitals | 35-50 | 90-100 |
| Colaboracion real-time | No | Si |
| Canvas libre (tipo Figma) | No | Si |
| Branching | No | Si |
| Modo offline | No | Si |
| Design tokens | No | Si |
| Animaciones avanzadas | Limitadas | Completas |

### Requisitos previos

- WordPress 6.0 o superior
- PHP 7.4 o superior
- Backup completo del sitio
- Acceso FTP/SSH o WP-CLI
- 2-4 horas (depende del tamano del sitio)

### Herramientas necesarias

```bash
# Verificar WP-CLI instalado
wp --version

# Verificar Flavor Platform
wp plugin is-active flavor-platform

# Verificar tema compatible
wp option get template
```

---

## Evaluacion del Sitio Actual

### 1. Inventario de paginas Elementor

```bash
# Listar paginas creadas con Elementor
wp db query "
  SELECT p.ID, p.post_title, p.post_type
  FROM wp_posts p
  INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
  WHERE pm.meta_key = '_elementor_edit_mode'
  AND pm.meta_value = 'builder'
  AND p.post_status = 'publish'
" --skip-column-names
```

### 2. Identificar templates globales

```bash
# Templates de Elementor (headers, footers, etc.)
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_type = 'elementor_library'
  AND post_status = 'publish'
" --skip-column-names
```

### 3. Listar widgets usados

```bash
# Extraer widgets unicos usados
wp db query "
  SELECT DISTINCT
    JSON_EXTRACT(pm.meta_value, '$[*].elements[*].widgetType') as widgets
  FROM wp_postmeta pm
  WHERE pm.meta_key = '_elementor_data'
" --skip-column-names | tr ',' '\n' | sort -u
```

### 4. Crear checklist de migracion

Crear archivo `migration-checklist.md`:

```markdown
# Checklist de Migracion

## Paginas a migrar
- [ ] Homepage
- [ ] About
- [ ] Servicios
- [ ] Contacto
- [ ] Blog archive
- [ ] Single post
- [ ] ...

## Templates globales
- [ ] Header
- [ ] Footer
- [ ] Sidebar
- [ ] 404

## Widgets criticos
- [ ] Formularios (Form widget -> VBP Forms)
- [ ] Sliders (Slides -> VBP Carousel)
- [ ] Popups (Popup Builder -> VBP Modals)
- [ ] ...

## Estilos globales
- [ ] Colores primarios/secundarios
- [ ] Tipografias
- [ ] Botones
- [ ] Espaciados
```

---

## Preparacion del Entorno

### 1. Instalar Flavor Platform

```bash
# Si no esta instalado
wp plugin install flavor-platform --activate

# Verificar
wp plugin is-active flavor-platform && echo "OK"
```

### 2. Configurar tema compatible

```bash
# Verificar tema flavor-starter
ls wp-content/themes/flavor-starter

# Si no existe, copiar
cp -r /path/to/flavor-starter wp-content/themes/

# Activar (CUIDADO: cambiara el frontend)
# Solo hacer cuando este listo para migrar
# wp theme activate flavor-starter
```

### 3. Obtener API key

```bash
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
echo "API Key: $API_KEY"

# Verificar API funciona
curl -s "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: $API_KEY"
```

### 4. Ejecutar inventario VBP

```bash
# Ver bloques disponibles
bash tools/vbp-inventory.sh "http://tu-sitio.local"
```

---

## Proceso de Migracion

### Estrategia recomendada: Migracion por fases

```
FASE 1: Preparacion (offline)
=============================
- Crear estructura VBP en staging
- Mapear widgets Elementor -> bloques VBP
- Preparar design tokens

FASE 2: Migracion de contenido
==============================
- Pagina por pagina
- Empezar por las menos criticas
- Validar cada pagina

FASE 3: Templates globales
==========================
- Header y footer
- Archive templates
- Single templates

FASE 4: Cutover
===============
- Activar tema VBP
- Desactivar Elementor
- Verificar todo
```

---

## Recreacion de Layouts

### Mapeo de widgets Elementor a bloques VBP

| Elementor Widget | VBP Block | Notas |
|------------------|-----------|-------|
| Heading | `heading` | Directo |
| Text Editor | `text` | Soporta HTML |
| Image | `image` | + lazy loading |
| Button | `button` | + variantes |
| Icon | `icon` | + SVG support |
| Divider | `divider` | - |
| Spacer | Usar CSS | No necesario |
| Google Maps | `map` | - |
| Video | `video` | - |
| Icon Box | `feature-card` | - |
| Image Box | `media-card` | - |
| Testimonial | `testimonial` | - |
| Tabs | `tabs` | - |
| Accordion | `accordion` | - |
| Counter | `counter` | Con animacion |
| Progress Bar | `progress` | - |
| Form | `form` | O shortcode |
| Slides | `carousel` | - |
| Posts | `post-grid` | Con query |
| Portfolio | `gallery` | Filtrable |
| Price Table | `pricing-card` | - |
| Flip Box | `flip-card` | - |
| Call to Action | `cta-section` | - |
| Section | `section` | Con background |
| Column | Via `columns` | Layout block |
| Inner Section | `container` | - |

### Ejemplo de conversion

**Elementor (Hero Section):**
```json
{
  "elType": "section",
  "settings": {
    "background_background": "gradient",
    "background_color": "#2d5016",
    "background_color_b": "#4a7c23"
  },
  "elements": [
    {
      "elType": "column",
      "elements": [
        {
          "widgetType": "heading",
          "settings": {
            "title": "Bienvenidos",
            "header_size": "h1",
            "align": "center"
          }
        },
        {
          "widgetType": "text-editor",
          "settings": {
            "editor": "<p>Subtitulo descriptivo</p>"
          }
        },
        {
          "widgetType": "button",
          "settings": {
            "text": "Saber mas",
            "link": {"url": "/about"}
          }
        }
      ]
    }
  ]
}
```

**VBP equivalente:**
```json
{
  "type": "section",
  "props": {
    "className": "hero-section",
    "background": {
      "type": "gradient",
      "from": "#2d5016",
      "to": "#4a7c23"
    }
  },
  "children": [
    {
      "type": "container",
      "children": [
        {
          "type": "heading",
          "props": {
            "level": 1,
            "text": "Bienvenidos",
            "align": "center",
            "color": "#fff"
          }
        },
        {
          "type": "text",
          "props": {
            "content": "Subtitulo descriptivo",
            "align": "center",
            "color": "#fff"
          }
        },
        {
          "type": "button",
          "props": {
            "text": "Saber mas",
            "url": "/about",
            "style": "primary",
            "size": "large"
          }
        }
      ]
    }
  ]
}
```

---

## Migracion de Contenido

### Script de extraccion de contenido Elementor

```bash
#!/bin/bash
# extract-elementor-content.sh

SITE_URL=$1
OUTPUT_DIR="./elementor-export"

mkdir -p "$OUTPUT_DIR"

# Obtener IDs de paginas Elementor
PAGE_IDS=$(wp db query "
  SELECT p.ID
  FROM wp_posts p
  INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
  WHERE pm.meta_key = '_elementor_edit_mode'
  AND pm.meta_value = 'builder'
  AND p.post_status = 'publish'
" --skip-column-names)

for ID in $PAGE_IDS; do
  TITLE=$(wp post get $ID --field=post_title)
  SLUG=$(wp post get $ID --field=post_name)

  echo "Exportando: $TITLE ($ID)"

  # Extraer datos de Elementor
  wp db query "
    SELECT meta_value
    FROM wp_postmeta
    WHERE post_id = $ID
    AND meta_key = '_elementor_data'
  " --skip-column-names > "$OUTPUT_DIR/${SLUG}_elementor.json"

  # Extraer contenido limpio (texto)
  wp post get $ID --field=post_content > "$OUTPUT_DIR/${SLUG}_content.txt"
done

echo "Exportacion completada en $OUTPUT_DIR"
```

### Script de conversion a VBP

```php
<?php
/**
 * Conversor Elementor a VBP
 * Ejecutar con: wp eval-file convert-to-vbp.php
 */

function convert_elementor_to_vbp( $elementor_data ) {
    $vbp_blocks = [];

    foreach ( $elementor_data as $element ) {
        $block = convert_element( $element );
        if ( $block ) {
            $vbp_blocks[] = $block;
        }
    }

    return $vbp_blocks;
}

function convert_element( $element ) {
    $type = $element['elType'] ?? $element['widgetType'] ?? '';
    $settings = $element['settings'] ?? [];
    $children = $element['elements'] ?? [];

    switch ( $type ) {
        case 'section':
            return [
                'type' => 'section',
                'props' => convert_section_props( $settings ),
                'children' => array_map( 'convert_element', $children ),
            ];

        case 'column':
            return [
                'type' => 'container',
                'children' => array_map( 'convert_element', $children ),
            ];

        case 'heading':
            $level = str_replace( 'h', '', $settings['header_size'] ?? 'h2' );
            return [
                'type' => 'heading',
                'props' => [
                    'level' => (int) $level,
                    'text' => $settings['title'] ?? '',
                    'align' => $settings['align'] ?? 'left',
                ],
            ];

        case 'text-editor':
            return [
                'type' => 'text',
                'props' => [
                    'content' => $settings['editor'] ?? '',
                ],
            ];

        case 'button':
            return [
                'type' => 'button',
                'props' => [
                    'text' => $settings['text'] ?? 'Click',
                    'url' => $settings['link']['url'] ?? '#',
                    'style' => 'primary',
                ],
            ];

        case 'image':
            return [
                'type' => 'image',
                'props' => [
                    'src' => $settings['image']['url'] ?? '',
                    'alt' => $settings['image']['alt'] ?? '',
                ],
            ];

        // Agregar mas casos segun necesites...

        default:
            // Elemento no soportado, convertir a HTML
            return null;
    }
}

function convert_section_props( $settings ) {
    $props = [];

    // Background
    if ( ! empty( $settings['background_background'] ) ) {
        if ( $settings['background_background'] === 'classic' ) {
            $props['background'] = [
                'type' => 'color',
                'color' => $settings['background_color'] ?? '#fff',
            ];
        } elseif ( $settings['background_background'] === 'gradient' ) {
            $props['background'] = [
                'type' => 'gradient',
                'from' => $settings['background_color'] ?? '#000',
                'to' => $settings['background_color_b'] ?? '#333',
            ];
        }
    }

    // Padding
    if ( ! empty( $settings['padding'] ) ) {
        $props['padding'] = sprintf(
            '%spx %spx %spx %spx',
            $settings['padding']['top'] ?? 0,
            $settings['padding']['right'] ?? 0,
            $settings['padding']['bottom'] ?? 0,
            $settings['padding']['left'] ?? 0
        );
    }

    return $props;
}

// Uso
$page_id = 123; // ID de pagina Elementor
$elementor_data = get_post_meta( $page_id, '_elementor_data', true );
$elementor_data = json_decode( $elementor_data, true );

$vbp_blocks = convert_elementor_to_vbp( $elementor_data );

// Crear pagina VBP
$api_key = flavor_get_vbp_api_key();
$response = wp_remote_post(
    home_url( '/wp-json/flavor-vbp/v1/claude/pages' ),
    [
        'headers' => [
            'X-VBP-Key' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode( [
            'title' => get_the_title( $page_id ) . ' (VBP)',
            'blocks' => $vbp_blocks,
        ] ),
    ]
);

$result = json_decode( wp_remote_retrieve_body( $response ), true );
echo "Pagina VBP creada: ID " . $result['id'];
```

---

## Widgets y Funcionalidades

### Formularios (Elementor Forms -> VBP Forms)

```bash
# Elementor Form fields -> VBP form config
# Crear formulario equivalente

curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/pages" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Contacto",
    "blocks": [
      {
        "type": "section",
        "children": [
          {
            "type": "form",
            "props": {
              "fields": [
                {"type": "text", "name": "nombre", "label": "Nombre", "required": true},
                {"type": "email", "name": "email", "label": "Email", "required": true},
                {"type": "textarea", "name": "mensaje", "label": "Mensaje"}
              ],
              "submitText": "Enviar",
              "successMessage": "Gracias por contactarnos"
            }
          }
        ]
      }
    ]
  }'
```

### Sliders/Carousels

```json
// Elementor Slides -> VBP Carousel
{
  "type": "carousel",
  "props": {
    "autoplay": true,
    "interval": 5000,
    "arrows": true,
    "dots": true
  },
  "children": [
    {
      "type": "slide",
      "props": {
        "background": {"type": "image", "src": "/slide1.jpg"},
        "overlay": "rgba(0,0,0,0.5)"
      },
      "children": [
        {"type": "heading", "props": {"text": "Slide 1", "color": "#fff"}},
        {"type": "button", "props": {"text": "Ver mas", "url": "/pagina1"}}
      ]
    },
    {
      "type": "slide",
      "props": {
        "background": {"type": "image", "src": "/slide2.jpg"}
      },
      "children": [
        {"type": "heading", "props": {"text": "Slide 2", "color": "#fff"}}
      ]
    }
  ]
}
```

### Popups (Elementor Popup Builder -> VBP Modals)

```json
// VBP Modal
{
  "type": "modal",
  "props": {
    "trigger": "button",
    "triggerText": "Abrir popup",
    "closeOnOverlay": true,
    "animation": "fade-in"
  },
  "children": [
    {"type": "heading", "props": {"text": "Titulo del popup"}},
    {"type": "text", "props": {"content": "Contenido del popup..."}},
    {"type": "button", "props": {"text": "Cerrar", "action": "close-modal"}}
  ]
}
```

---

## Templates Globales

### Header

```bash
# Crear header global en VBP
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Header Principal",
    "type": "header",
    "blocks": [
      {
        "type": "header",
        "props": {
          "sticky": true,
          "transparent": false,
          "background": "#ffffff"
        },
        "children": [
          {
            "type": "container",
            "props": {"layout": "flex", "justify": "space-between", "align": "center"},
            "children": [
              {"type": "logo", "props": {"src": "/logo.svg", "width": 150}},
              {"type": "nav-menu", "props": {"menu": "primary", "style": "horizontal"}},
              {"type": "button", "props": {"text": "Contacto", "url": "/contacto", "style": "primary"}}
            ]
          }
        ]
      }
    ]
  }'
```

### Footer

```bash
# Crear footer global
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Footer Principal",
    "type": "footer",
    "blocks": [
      {
        "type": "footer",
        "props": {"background": "#1f2937", "color": "#ffffff"},
        "children": [
          {
            "type": "columns",
            "props": {"columns": 4, "gap": "30px"},
            "children": [
              {
                "type": "container",
                "children": [
                  {"type": "logo", "props": {"src": "/logo-white.svg"}},
                  {"type": "text", "props": {"content": "Descripcion de la empresa"}}
                ]
              },
              {
                "type": "container",
                "children": [
                  {"type": "heading", "props": {"level": 4, "text": "Enlaces"}},
                  {"type": "nav-menu", "props": {"menu": "footer", "style": "vertical"}}
                ]
              },
              {
                "type": "container",
                "children": [
                  {"type": "heading", "props": {"level": 4, "text": "Contacto"}},
                  {"type": "text", "props": {"content": "info@empresa.com<br>Tel: 944 123 456"}}
                ]
              },
              {
                "type": "container",
                "children": [
                  {"type": "heading", "props": {"level": 4, "text": "Siguenos"}},
                  {"type": "social-links", "props": {"networks": ["facebook", "twitter", "instagram"]}}
                ]
              }
            ]
          },
          {
            "type": "container",
            "props": {"borderTop": "1px solid #374151", "padding": "20px 0", "marginTop": "40px"},
            "children": [
              {"type": "text", "props": {"content": "2026 Empresa. Todos los derechos reservados.", "align": "center"}}
            ]
          }
        ]
      }
    ]
  }'
```

---

## Estilos y Theme Builder

### Migrar colores globales de Elementor

```bash
# Extraer colores de Elementor
wp db query "
  SELECT option_value
  FROM wp_options
  WHERE option_name = 'elementor_scheme_color'
" --skip-column-names > elementor-colors.json

# Crear design tokens en VBP
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "colors": {
      "primary": "#2d5016",
      "secondary": "#4a7c23",
      "accent": "#f59e0b",
      "text": "#1f2937",
      "background": "#ffffff"
    },
    "typography": {
      "font-family": "Inter, sans-serif",
      "heading-family": "Poppins, sans-serif",
      "base-size": "16px",
      "scale": 1.25
    },
    "spacing": {
      "xs": "4px",
      "sm": "8px",
      "md": "16px",
      "lg": "32px",
      "xl": "64px"
    }
  }'
```

### Migrar tipografias

```bash
# Verificar fonts usadas en Elementor
wp db query "
  SELECT option_value
  FROM wp_options
  WHERE option_name = 'elementor_scheme_typography'
" --skip-column-names

# Las fonts se configuran en design tokens (ver arriba)
# VBP carga fonts de Google Fonts automaticamente
```

---

## Verificacion y Testing

### Checklist de verificacion

```bash
#!/bin/bash
# verify-migration.sh

SITE_URL=$1
API_KEY=$2

echo "=== Verificacion de Migracion ==="

# 1. Verificar paginas VBP creadas
echo "1. Paginas VBP:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/pages" \
  -H "X-VBP-Key: $API_KEY" | jq '.pages | length'

# 2. Verificar templates
echo "2. Templates:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" | jq '.templates | length'

# 3. Verificar design tokens
echo "3. Design tokens:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" | jq 'keys'

# 4. Performance check
echo "4. Performance:"
curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=$SITE_URL" \
  | jq '.lighthouseResult.categories.performance.score'

# 5. Errores JS en consola
echo "5. Verificar manualmente errores JS en navegador"

echo "=== Verificacion completada ==="
```

### Comparacion visual

1. Abrir pagina original (Elementor) en tab 1
2. Abrir pagina migrada (VBP) en tab 2
3. Comparar visualmente cada seccion
4. Verificar responsive (mobile, tablet)
5. Testear interacciones (forms, sliders, popups)

### Testing de performance

```bash
# Antes (con Elementor)
lighthouse http://tu-sitio.local --output=json --output-path=./before.json

# Despues (con VBP)
# (despues de activar tema VBP)
lighthouse http://tu-sitio.local --output=json --output-path=./after.json

# Comparar
jq '.categories.performance.score' before.json after.json
```

---

## Desactivacion de Elementor

### Proceso seguro

```bash
# 1. BACKUP COMPLETO
wp db export backup-pre-elementor-removal.sql
tar -czf backup-uploads.tar.gz wp-content/uploads/

# 2. Verificar que todas las paginas estan migradas
# Listar paginas que aun usan Elementor
wp db query "
  SELECT p.ID, p.post_title
  FROM wp_posts p
  INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
  WHERE pm.meta_key = '_elementor_edit_mode'
  AND pm.meta_value = 'builder'
  AND p.post_status = 'publish'
" --skip-column-names

# Si hay paginas pendientes, migrarlas primero

# 3. Desactivar Elementor
wp plugin deactivate elementor elementor-pro

# 4. Verificar sitio funciona
curl -I http://tu-sitio.local | head -1
# Debe mostrar: HTTP/1.1 200 OK

# 5. Si todo OK, limpiar
# CUIDADO: Esto elimina datos de Elementor
# wp db query "DELETE FROM wp_postmeta WHERE meta_key LIKE '_elementor%'"
# wp plugin delete elementor elementor-pro
```

### Rollback si hay problemas

```bash
# Restaurar backup
wp db import backup-pre-elementor-removal.sql
wp plugin activate elementor elementor-pro
wp cache flush
```

---

## FAQ

### P: Puedo migrar parcialmente?

**R:** Si. Puedes tener paginas con Elementor y paginas con VBP simultaneamente. Solo asegurate de que el tema soporta ambos.

### P: Se pierden los datos de Elementor al migrar?

**R:** No. La migracion crea nuevas paginas VBP. Los originales de Elementor permanecen hasta que los elimines manualmente.

### P: Como manejo addons de Elementor (Essential Addons, etc.)?

**R:** Identifica que widgets de addons usas y busca equivalentes en VBP. La mayoria tienen alternativa nativa.

### P: Y si uso Elementor Pro con Theme Builder?

**R:** VBP tiene su propio sistema de templates. Recrea header/footer/archives en VBP antes de desactivar Elementor.

### P: Cuanto mejora el performance?

**R:** Tipicamente:
- LCP: de 4-6s a 1.5-2s (mejora 60-75%)
- Performance Score: de 40-50 a 90-100
- HTML size: reduccion 80%+

### P: Hay herramienta automatica de conversion?

**R:** El script PHP incluido hace conversion basica. Para layouts complejos, es mejor recrear manualmente para optimizar.

### P: Puedo volver a Elementor despues?

**R:** Si mantienes el backup y no eliminas Elementor/datos, puedes volver. Pero una vez migrado, no hay razon para volver.

---

## Recursos Adicionales

- [Documentacion VBP](./vbp/README.md)
- [API Reference](./vbp/api/rest-endpoints.md)
- [Bloques disponibles](./vbp/api/blocks.md)
- [Design tokens](./vbp/features/design-tokens.md)

---

*Guia actualizada: Abril 2026*
*Version: 1.0*
