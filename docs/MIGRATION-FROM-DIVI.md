# Migracion de Divi a VBP

Guia paso a paso para migrar sitios de Divi Builder a Visual Builder Pro.

---

## Indice

1. [Antes de Empezar](#antes-de-empezar)
2. [Diferencias Fundamentales](#diferencias-fundamentales)
3. [Evaluacion del Sitio](#evaluacion-del-sitio)
4. [Preparacion](#preparacion)
5. [Mapeo de Modulos](#mapeo-de-modulos)
6. [Proceso de Migracion](#proceso-de-migracion)
7. [Layouts Globales](#layouts-globales)
8. [Divi Theme Builder](#divi-theme-builder)
9. [Estilos y Presets](#estilos-y-presets)
10. [Verificacion](#verificacion)
11. [Transicion Final](#transicion-final)

---

## Antes de Empezar

### Comparativa Divi vs VBP

| Aspecto | Divi | VBP |
|---------|------|-----|
| Tipo | Tema + Builder | Plugin Builder |
| Output HTML | Muy pesado (700KB+) | Limpio (15KB) |
| CSS | 600KB+ inline | 45KB optimizado |
| Performance Score | 25-40 | 90-100 |
| jQuery | Requerido | No necesario |
| Canvas libre | No | Si (tipo Figma) |
| Colaboracion | No | Real-time |
| Branching | No | Si |
| Design tokens | No | Si |
| Offline mode | No | Si |

### Por que migrar

1. **Performance**: Divi es uno de los builders mas pesados
2. **Codigo limpio**: Output semantico y ligero
3. **Colaboracion**: Trabajo en equipo real-time
4. **Futuro**: Divi no ha innovado significativamente

### Retos especificos de Divi

- Divi es tema + builder (acoplado)
- Usa shortcodes propietarios
- El contenido depende del tema activo
- Muchos usuarios dependen de layouts de Elegant Themes

---

## Diferencias Fundamentales

### Arquitectura

```
DIVI:
+------------------+
| Divi Theme       |  <- Tema obligatorio
+--------+---------+
         |
+--------v---------+
| Divi Builder     |  <- Acoplado al tema
+--------+---------+
         |
+--------v---------+
| Shortcodes [et_] |  <- Propietario
+------------------+

VBP:
+------------------+
| Cualquier tema   |  <- Flexible
+--------+---------+
         |
+--------v---------+
| VBP Plugin       |  <- Independiente
+--------+---------+
         |
+--------v---------+
| JSON/HTML limpio |  <- Estandar
+------------------+
```

### Contenido en base de datos

**Divi guarda:**
```
[et_pb_section admin_label="Hero"][et_pb_row][et_pb_column type="4_4"]
[et_pb_text admin_label="Title"]<h1>Titulo</h1>[/et_pb_text]
[/et_pb_column][/et_pb_row][/et_pb_section]
```

**VBP guarda:**
```json
{
  "type": "section",
  "props": {"className": "hero"},
  "children": [
    {"type": "heading", "props": {"level": 1, "text": "Titulo"}}
  ]
}
```

---

## Evaluacion del Sitio

### 1. Inventario de paginas Divi

```bash
# Listar paginas con Divi Builder
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_content LIKE '%[et_pb_%'
  AND post_status = 'publish'
  AND post_type IN ('page', 'post')
" --skip-column-names
```

### 2. Layouts guardados en biblioteca

```bash
# Listar layouts de Divi Library
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_type = 'et_pb_layout'
  AND post_status = 'publish'
" --skip-column-names
```

### 3. Modulos usados

```bash
# Extraer modulos unicos
wp db query "
  SELECT post_content
  FROM wp_posts
  WHERE post_content LIKE '%[et_pb_%'
  AND post_status = 'publish'
" --skip-column-names | grep -oP '\[et_pb_\w+' | sort -u
```

### 4. Theme Builder templates

```bash
# Si usas Divi Theme Builder
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_type = 'et_template'
  AND post_status = 'publish'
" --skip-column-names
```

---

## Preparacion

### 1. Backup completo

```bash
# Base de datos
wp db export divi-backup-$(date +%Y%m%d).sql

# Archivos
tar -czf divi-files-backup.tar.gz wp-content/themes/Divi wp-content/uploads/

# Opciones de Divi
wp option get et_divi > divi-options.json
```

### 2. Instalar Flavor Platform

```bash
# Instalar si no esta
wp plugin install flavor-platform --activate

# Verificar
wp plugin is-active flavor-platform
```

### 3. Preparar tema alternativo

```bash
# Verificar tema flavor-starter disponible
ls wp-content/themes/flavor-starter

# O usar tema compatible (cualquier tema bien codificado)
# NO activar todavia
```

### 4. Obtener API key VBP

```bash
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
echo "API Key: $API_KEY"

# Test
curl -s "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/status" \
  -H "X-VBP-Key: $API_KEY"
```

---

## Mapeo de Modulos

### Modulos Divi -> Bloques VBP

| Divi Module | VBP Block | Notas |
|-------------|-----------|-------|
| `et_pb_text` | `text` | Soporta HTML |
| `et_pb_blurb` | `feature-card` | - |
| `et_pb_button` | `button` | + variantes |
| `et_pb_image` | `image` | + lazy loading |
| `et_pb_video` | `video` | - |
| `et_pb_slider` | `carousel` | - |
| `et_pb_gallery` | `gallery` | Filtrable |
| `et_pb_testimonial` | `testimonial` | - |
| `et_pb_pricing_tables` | `pricing-card` | - |
| `et_pb_cta` | `cta-section` | - |
| `et_pb_contact_form` | `form` | - |
| `et_pb_countdown_timer` | `countdown` | - |
| `et_pb_number_counter` | `counter` | - |
| `et_pb_circle_counter` | `progress` | - |
| `et_pb_bar_counters` | `progress` | - |
| `et_pb_accordion` | `accordion` | - |
| `et_pb_tabs` | `tabs` | - |
| `et_pb_toggle` | `accordion` | Single |
| `et_pb_map` | `map` | - |
| `et_pb_social_media_follow` | `social-links` | - |
| `et_pb_blog` | `post-grid` | - |
| `et_pb_portfolio` | `gallery` | - |
| `et_pb_filterable_portfolio` | `gallery` | Filtrable |
| `et_pb_shop` | Via WooCommerce | - |
| `et_pb_sidebar` | `sidebar` | - |
| `et_pb_divider` | `divider` | - |
| `et_pb_code` | `code` | - |
| `et_pb_fullwidth_*` | `section` fullwidth | - |

### Estructura Divi -> VBP

| Divi | VBP |
|------|-----|
| Section | `section` |
| Row | Implicito en layout |
| Column | Via `columns` block |
| Module | Block especifico |

---

## Proceso de Migracion

### Script de extraccion Divi

```php
<?php
/**
 * Extractor de contenido Divi
 * wp eval-file extract-divi.php
 */

function extract_divi_content( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );

    // Parsear shortcodes Divi
    $sections = parse_divi_sections( $content );

    return $sections;
}

function parse_divi_sections( $content ) {
    $sections = [];

    // Regex para secciones
    preg_match_all(
        '/\[et_pb_section([^\]]*)\](.*?)\[\/et_pb_section\]/s',
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $section = [
            'type' => 'section',
            'attrs' => parse_shortcode_attrs( $match[1] ),
            'children' => parse_divi_rows( $match[2] ),
        ];
        $sections[] = $section;
    }

    return $sections;
}

function parse_divi_rows( $content ) {
    $rows = [];

    preg_match_all(
        '/\[et_pb_row([^\]]*)\](.*?)\[\/et_pb_row\]/s',
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $row = [
            'type' => 'row',
            'attrs' => parse_shortcode_attrs( $match[1] ),
            'children' => parse_divi_columns( $match[2] ),
        ];
        $rows[] = $row;
    }

    return $rows;
}

function parse_divi_columns( $content ) {
    $columns = [];

    preg_match_all(
        '/\[et_pb_column([^\]]*)\](.*?)\[\/et_pb_column\]/s',
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $column = [
            'type' => 'column',
            'attrs' => parse_shortcode_attrs( $match[1] ),
            'children' => parse_divi_modules( $match[2] ),
        ];
        $columns[] = $column;
    }

    return $columns;
}

function parse_divi_modules( $content ) {
    $modules = [];

    // Modulos autocerrantes y con contenido
    preg_match_all(
        '/\[et_pb_(\w+)([^\]]*?)(?:\/\]|\](.*?)\[\/et_pb_\1\])/s',
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $module = [
            'type' => $match[1],
            'attrs' => parse_shortcode_attrs( $match[2] ),
            'content' => $match[3] ?? '',
        ];
        $modules[] = $module;
    }

    return $modules;
}

function parse_shortcode_attrs( $attr_string ) {
    $attrs = [];

    preg_match_all(
        '/(\w+)=["\']([^"\']*)["\']/',
        $attr_string,
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $attrs[ $match[1] ] = $match[2];
    }

    return $attrs;
}

// Exportar
$page_id = 123;
$divi_structure = extract_divi_content( $page_id );
file_put_contents(
    'divi-export-' . $page_id . '.json',
    json_encode( $divi_structure, JSON_PRETTY_PRINT )
);
echo "Exportado: divi-export-$page_id.json\n";
```

### Script de conversion Divi a VBP

```php
<?php
/**
 * Conversor Divi a VBP
 * wp eval-file convert-divi-to-vbp.php
 */

function convert_divi_to_vbp( $divi_structure ) {
    $vbp_blocks = [];

    foreach ( $divi_structure as $section ) {
        $vbp_blocks[] = convert_divi_section( $section );
    }

    return array_filter( $vbp_blocks );
}

function convert_divi_section( $section ) {
    $children = [];

    foreach ( $section['children'] ?? [] as $row ) {
        $row_children = convert_divi_row( $row );
        $children = array_merge( $children, $row_children );
    }

    return [
        'type' => 'section',
        'props' => convert_section_props( $section['attrs'] ?? [] ),
        'children' => $children,
    ];
}

function convert_divi_row( $row ) {
    $columns = $row['children'] ?? [];
    $column_count = count( $columns );

    if ( $column_count === 1 ) {
        // Una sola columna, no necesita wrapper
        return convert_divi_modules( $columns[0]['children'] ?? [] );
    }

    // Multiples columnas
    $column_children = [];
    foreach ( $columns as $col ) {
        $column_children[] = [
            'type' => 'container',
            'children' => convert_divi_modules( $col['children'] ?? [] ),
        ];
    }

    return [
        [
            'type' => 'columns',
            'props' => [
                'columns' => $column_count,
                'gap' => '30px',
            ],
            'children' => $column_children,
        ],
    ];
}

function convert_divi_modules( $modules ) {
    $vbp_blocks = [];

    foreach ( $modules as $module ) {
        $block = convert_divi_module( $module );
        if ( $block ) {
            $vbp_blocks[] = $block;
        }
    }

    return $vbp_blocks;
}

function convert_divi_module( $module ) {
    $type = $module['type'];
    $attrs = $module['attrs'] ?? [];
    $content = $module['content'] ?? '';

    switch ( $type ) {
        case 'text':
            return [
                'type' => 'text',
                'props' => [
                    'content' => wp_kses_post( $content ),
                ],
            ];

        case 'blurb':
            return [
                'type' => 'feature-card',
                'props' => [
                    'icon' => $attrs['font_icon'] ?? '',
                    'title' => $attrs['title'] ?? '',
                    'text' => wp_kses_post( $content ),
                ],
            ];

        case 'button':
            return [
                'type' => 'button',
                'props' => [
                    'text' => $attrs['button_text'] ?? 'Click',
                    'url' => $attrs['button_url'] ?? '#',
                    'style' => 'primary',
                ],
            ];

        case 'image':
            return [
                'type' => 'image',
                'props' => [
                    'src' => $attrs['src'] ?? '',
                    'alt' => $attrs['alt'] ?? '',
                ],
            ];

        case 'video':
            return [
                'type' => 'video',
                'props' => [
                    'src' => $attrs['src'] ?? '',
                ],
            ];

        case 'slider':
        case 'fullwidth_slider':
            return convert_divi_slider( $module );

        case 'testimonial':
            return [
                'type' => 'testimonial',
                'props' => [
                    'author' => $attrs['author'] ?? '',
                    'company' => $attrs['company_name'] ?? '',
                    'portrait' => $attrs['portrait_url'] ?? '',
                    'quote' => wp_kses_post( $content ),
                ],
            ];

        case 'pricing_tables':
            return convert_divi_pricing( $module );

        case 'cta':
        case 'fullwidth_cta':
            return [
                'type' => 'cta-section',
                'props' => [
                    'title' => $attrs['title'] ?? '',
                    'buttonText' => $attrs['button_text'] ?? '',
                    'buttonUrl' => $attrs['button_url'] ?? '#',
                ],
                'children' => [
                    ['type' => 'text', 'props' => ['content' => $content]],
                ],
            ];

        case 'contact_form':
            return convert_divi_form( $module );

        case 'accordion':
            return convert_divi_accordion( $module );

        case 'tabs':
            return convert_divi_tabs( $module );

        case 'gallery':
        case 'fullwidth_gallery':
            return [
                'type' => 'gallery',
                'props' => [
                    'ids' => $attrs['gallery_ids'] ?? '',
                    'columns' => (int) ( $attrs['posts_number'] ?? 4 ),
                ],
            ];

        case 'blog':
        case 'fullwidth_post_slider':
            return [
                'type' => 'post-grid',
                'props' => [
                    'postsPerPage' => (int) ( $attrs['posts_number'] ?? 6 ),
                    'category' => $attrs['include_categories'] ?? '',
                ],
            ];

        case 'map':
        case 'fullwidth_map':
            return [
                'type' => 'map',
                'props' => [
                    'address' => $attrs['address'] ?? '',
                    'zoom' => (int) ( $attrs['zoom_level'] ?? 14 ),
                ],
            ];

        case 'social_media_follow':
            return [
                'type' => 'social-links',
                'props' => [
                    'networks' => extract_social_networks( $content ),
                ],
            ];

        case 'divider':
            return [
                'type' => 'divider',
                'props' => [
                    'style' => $attrs['divider_style'] ?? 'solid',
                ],
            ];

        case 'code':
        case 'fullwidth_code':
            return [
                'type' => 'code',
                'props' => [
                    'code' => $content,
                ],
            ];

        default:
            // Modulo no soportado, convertir a HTML si tiene contenido
            if ( ! empty( $content ) ) {
                return [
                    'type' => 'text',
                    'props' => [
                        'content' => '<!-- Divi: ' . $type . ' -->' . wp_kses_post( $content ),
                    ],
                ];
            }
            return null;
    }
}

function convert_section_props( $attrs ) {
    $props = [];

    // Background
    if ( ! empty( $attrs['background_color'] ) ) {
        $props['background'] = [
            'type' => 'color',
            'color' => $attrs['background_color'],
        ];
    }

    if ( ! empty( $attrs['background_image'] ) ) {
        $props['background'] = [
            'type' => 'image',
            'src' => $attrs['background_image'],
        ];
    }

    // Fullwidth
    if ( ! empty( $attrs['fullwidth'] ) && $attrs['fullwidth'] === 'on' ) {
        $props['fullWidth'] = true;
    }

    return $props;
}

// Funciones auxiliares para modulos complejos
function convert_divi_slider( $module ) {
    // Parsear slides del contenido
    $slides = [];
    preg_match_all(
        '/\[et_pb_slide([^\]]*)\](.*?)\[\/et_pb_slide\]/s',
        $module['content'],
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $attrs = parse_shortcode_attrs( $match[1] );
        $slides[] = [
            'type' => 'slide',
            'props' => [
                'background' => [
                    'type' => 'image',
                    'src' => $attrs['background_image'] ?? '',
                ],
            ],
            'children' => [
                ['type' => 'heading', 'props' => ['text' => $attrs['heading'] ?? '', 'color' => '#fff']],
                ['type' => 'text', 'props' => ['content' => $match[2]]],
            ],
        ];
    }

    return [
        'type' => 'carousel',
        'props' => ['autoplay' => true, 'arrows' => true],
        'children' => $slides,
    ];
}

function convert_divi_pricing( $module ) {
    // Simplificado - parsear pricing items
    return [
        'type' => 'pricing-card',
        'props' => [
            'title' => 'Plan',
            'price' => '$0',
        ],
    ];
}

function convert_divi_form( $module ) {
    return [
        'type' => 'form',
        'props' => [
            'fields' => [
                ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message'],
            ],
        ],
    ];
}

function convert_divi_accordion( $module ) {
    $items = [];
    preg_match_all(
        '/\[et_pb_accordion_item([^\]]*)\](.*?)\[\/et_pb_accordion_item\]/s',
        $module['content'],
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $attrs = parse_shortcode_attrs( $match[1] );
        $items[] = [
            'title' => $attrs['title'] ?? '',
            'content' => wp_kses_post( $match[2] ),
        ];
    }

    return [
        'type' => 'accordion',
        'props' => ['items' => $items],
    ];
}

function convert_divi_tabs( $module ) {
    $tabs = [];
    preg_match_all(
        '/\[et_pb_tab([^\]]*)\](.*?)\[\/et_pb_tab\]/s',
        $module['content'],
        $matches,
        PREG_SET_ORDER
    );

    foreach ( $matches as $match ) {
        $attrs = parse_shortcode_attrs( $match[1] );
        $tabs[] = [
            'label' => $attrs['title'] ?? '',
            'content' => wp_kses_post( $match[2] ),
        ];
    }

    return [
        'type' => 'tabs',
        'props' => ['tabs' => $tabs],
    ];
}

function extract_social_networks( $content ) {
    $networks = [];
    preg_match_all(
        '/\[et_pb_social_media_follow_network\s+social_network="(\w+)"/s',
        $content,
        $matches
    );

    return $matches[1] ?? [];
}

// Main
$page_id = 123;
$divi_json = file_get_contents( "divi-export-$page_id.json" );
$divi_structure = json_decode( $divi_json, true );

$vbp_blocks = convert_divi_to_vbp( $divi_structure );

// Crear pagina VBP
$api_key = flavor_get_vbp_api_key();
$title = get_the_title( $page_id );

$response = wp_remote_post(
    home_url( '/wp-json/flavor-vbp/v1/claude/pages' ),
    [
        'headers' => [
            'X-VBP-Key' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode( [
            'title' => $title . ' (VBP)',
            'blocks' => $vbp_blocks,
        ] ),
    ]
);

$result = json_decode( wp_remote_retrieve_body( $response ), true );
echo "Pagina VBP creada: ID " . $result['id'] . "\n";
```

---

## Layouts Globales

### Migrar layouts de Divi Library

```bash
#!/bin/bash
# migrate-divi-layouts.sh

# Listar layouts
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_type = 'et_pb_layout'
  AND post_status = 'publish'
" --skip-column-names | while read ID TITLE; do
  echo "Migrando layout: $TITLE ($ID)"

  # Exportar
  wp eval-file extract-divi.php $ID

  # Convertir
  wp eval-file convert-divi-to-vbp.php $ID
done
```

---

## Divi Theme Builder

### Migrar header

```bash
# Obtener header de Divi Theme Builder
HEADER_ID=$(wp db query "
  SELECT ID FROM wp_posts
  WHERE post_type = 'et_template'
  AND post_title LIKE '%header%'
  LIMIT 1
" --skip-column-names)

# Crear header VBP equivalente
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Header Principal",
    "type": "header",
    "blocks": [
      {
        "type": "header",
        "props": {"sticky": true},
        "children": [
          {
            "type": "container",
            "props": {"layout": "flex", "justify": "space-between"},
            "children": [
              {"type": "logo"},
              {"type": "nav-menu", "props": {"menu": "primary"}},
              {"type": "button", "props": {"text": "Contacto", "url": "/contacto"}}
            ]
          }
        ]
      }
    ]
  }'
```

### Migrar footer

```bash
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Footer Principal",
    "type": "footer",
    "blocks": [
      {
        "type": "footer",
        "props": {"background": "#1f2937"},
        "children": [
          {
            "type": "columns",
            "props": {"columns": 4},
            "children": [
              {"type": "container", "children": [{"type": "logo"}, {"type": "text", "props": {"content": "Descripcion"}}]},
              {"type": "container", "children": [{"type": "nav-menu", "props": {"menu": "footer"}}]},
              {"type": "container", "children": [{"type": "text", "props": {"content": "Contacto info"}}]},
              {"type": "container", "children": [{"type": "social-links"}]}
            ]
          }
        ]
      }
    ]
  }'
```

---

## Estilos y Presets

### Extraer colores de Divi

```bash
# Colores principales de Divi
wp option get et_divi --format=json | jq '{
  primary: .accent_color,
  secondary: .link_color,
  heading: .heading_color,
  body: .body_text_color,
  background: .background_color
}'
```

### Crear design tokens VBP

```bash
# Con los colores extraidos, crear tokens
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "colors": {
      "primary": "#7c3aed",
      "secondary": "#3b82f6",
      "accent": "#f59e0b",
      "heading": "#1f2937",
      "body": "#4b5563",
      "background": "#ffffff"
    },
    "typography": {
      "heading-family": "Poppins, sans-serif",
      "body-family": "Open Sans, sans-serif",
      "base-size": "16px"
    }
  }'
```

### Migrar presets de modulos

Divi permite guardar presets por modulo. Identificar los mas usados:

```bash
# Presets de Divi
wp option get et_pb_module_presets
```

Y recrear como Global Styles en VBP:

```bash
curl -X POST "http://tu-sitio.local/wp-json/flavor-vbp/v1/claude/global-styles" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Button Primary",
    "selector": ".btn-primary",
    "styles": {
      "background": "var(--color-primary)",
      "color": "#ffffff",
      "padding": "12px 24px",
      "borderRadius": "4px",
      "fontWeight": "600"
    }
  }'
```

---

## Verificacion

### Checklist de migracion

```bash
#!/bin/bash
# verify-divi-migration.sh

echo "=== Verificacion de Migracion Divi a VBP ==="

# 1. Paginas migradas
echo "1. Paginas VBP creadas:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/pages" \
  -H "X-VBP-Key: $API_KEY" | jq '.pages | length'

# 2. Templates
echo "2. Templates VBP:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" | jq '.templates[] | .name'

# 3. Design tokens
echo "3. Design tokens configurados:"
curl -s "$SITE_URL/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" | jq 'keys'

# 4. Paginas Divi restantes
echo "4. Paginas aun con Divi:"
wp db query "
  SELECT COUNT(*) FROM wp_posts
  WHERE post_content LIKE '%[et_pb_%'
  AND post_status = 'publish'
" --skip-column-names

# 5. Performance
echo "5. Test de performance:"
curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=$SITE_URL" \
  | jq '.lighthouseResult.categories.performance.score'

echo "=== Verificacion completada ==="
```

### Comparacion visual

1. Activar tema flavor-starter en staging
2. Comparar pagina original vs migrada
3. Verificar responsive
4. Testear interacciones

---

## Transicion Final

### 1. Activar tema compatible

```bash
# Solo cuando todo este migrado
wp theme activate flavor-starter

# Verificar
curl -I http://tu-sitio.local | head -1
```

### 2. Desactivar Divi

```bash
# NO eliminar todavia, solo desactivar
# Si Divi es tema, cambiar a otro
wp theme activate flavor-starter

# Si tienes Divi Builder plugin
wp plugin deactivate divi-builder
```

### 3. Limpiar shortcodes huerfanos

```bash
# Buscar contenido con shortcodes Divi rotos
wp db query "
  SELECT ID, post_title
  FROM wp_posts
  WHERE post_content LIKE '%[et_pb_%'
  AND post_status = 'publish'
" --skip-column-names
```

Si hay contenido con shortcodes, convertir manualmente o limpiar.

### 4. Eliminar Divi (opcional)

```bash
# Solo si estas seguro
# wp theme delete Divi
# wp plugin delete divi-builder

# Limpiar opciones
# wp option delete et_divi
# wp option delete et_pb_module_presets
```

---

## FAQ Especifico de Divi

### P: Tengo cientos de paginas con Divi, es viable migrar?

**R:** Si, pero considera:
- Usar el script automatizado para las paginas mas criticas
- Paginas simples pueden recrearse rapidamente con VBP
- Paginas legacy pueden mantenerse temporalmente

### P: Uso Divi como tema, no solo el builder. Que hago?

**R:** Divi como tema esta muy acoplado. Necesitas:
1. Instalar tema alternativo (flavor-starter)
2. Migrar TODO antes de cambiar tema
3. Probar exhaustivamente en staging

### P: Los premade layouts de Elegant Themes funcionaran?

**R:** No directamente. Pero VBP tiene sus propias plantillas y presets que son mas modernos y mejor optimizados.

### P: Mis plugins de terceros para Divi funcionaran?

**R:** No. Pero la mayoria de funcionalidades tienen equivalente nativo en VBP (sliders, forms, etc.).

### P: El performance mejorara significativamente?

**R:** Si. Tipicamente:
- De 700KB CSS a 45KB
- De 5s LCP a 1.5s
- De score 30 a score 95

---

## Recursos

- [Documentacion VBP](./vbp/README.md)
- [Bloques disponibles](./vbp/api/blocks.md)
- [Design tokens](./vbp/features/design-tokens.md)
- [Matriz competitiva](./COMPETITIVE-MATRIX.md)

---

*Guia actualizada: Abril 2026*
*Version: 1.0*
