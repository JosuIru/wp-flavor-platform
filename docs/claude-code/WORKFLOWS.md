# Claude Code - Workflows para Crear Sitios

Guía paso a paso para crear sitios completos con Flavor Platform desde Claude Code.

---

## Flujo Completo: Crear Sitio desde Cero

### Paso 1: Verificar Módulos Disponibles

```bash
# Listar módulos instalados
curl -X GET "https://sitio.local/wp-json/flavor-modules/v1/list" \
  -H "Authorization: Bearer TOKEN"
```

**Respuesta:**
```json
{
  "modules": [
    {"id": "grupos_consumo", "name": "Grupos de Consumo", "active": true},
    {"id": "eventos", "name": "Eventos", "active": true},
    {"id": "socios", "name": "Socios", "active": false}
  ]
}
```

### Paso 2: Activar Módulos Necesarios

```bash
curl -X POST "https://sitio.local/wp-json/flavor-site-builder/v1/modules/activate" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "modules": ["grupos_consumo", "eventos", "socios", "marketplace"]
  }'
```

### Paso 3: Obtener Templates Disponibles

```bash
curl -X GET "https://sitio.local/wp-json/flavor-site-builder/v1/templates"
```

**Templates disponibles:**
| ID | Nombre | Módulos Requeridos |
|----|--------|-------------------|
| `grupo_consumo` | Grupo de Consumo | grupos_consumo |
| `cooperativa` | Cooperativa | socios, eventos |
| `marketplace` | Marketplace | marketplace |
| `red_social` | Red Social | red_social, comunidades |
| `municipalidad` | Municipalidad | transparencia, participacion |
| `asociacion` | Asociación | socios, eventos |
| `coworking` | Coworking | reservas, espacios_comunes |

### Paso 4: Crear Sitio con Template

```bash
curl -X POST "https://sitio.local/wp-json/flavor-site-builder/v1/site/create" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_sitio": "Cooperativa Verde",
    "template": "grupo_consumo",
    "perfil": "eco_community",
    "modulos": ["grupos_consumo", "eventos", "socios"],
    "diseño": {
      "primary_color": "#22c55e",
      "secondary_color": "#15803d",
      "font_family": "Inter"
    },
    "demo_data": true,
    "crear_menu": true,
    "crear_paginas": true
  }'
```

**Respuesta:**
```json
{
  "success": true,
  "site": {
    "name": "Cooperativa Verde",
    "pages_created": 8,
    "menu_id": 123,
    "landing_id": 456
  }
}
```

---

## Flujo: Crear Landing Page Personalizada

### Paso 1: Crear el Post de Landing

```bash
curl -X POST "https://sitio.local/wp-json/wp/v2/flavor_landing" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Inicio - Nuestra Comunidad",
    "status": "publish"
  }'
```

**Respuesta:** `{"id": 789, ...}`

### Paso 2: Añadir Contenido VBP

```bash
curl -X POST "https://sitio.local/wp-json/flavor-vbp/v1/documents/789" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "elements": [
      {
        "id": "el_hero_001",
        "type": "hero",
        "variant": "fullscreen",
        "data": {
          "titulo": "Bienvenido a nuestra comunidad",
          "subtitulo": "Conectando personas, construyendo futuro",
          "boton_texto": "Únete ahora",
          "boton_url": "/registro",
          "imagen_fondo": "/wp-content/uploads/hero-bg.jpg",
          "overlay_color": "#000000",
          "overlay_opacity": 40,
          "altura": "100vh"
        }
      },
      {
        "id": "el_features_001",
        "type": "features",
        "variant": "cards",
        "data": {
          "titulo": "¿Por qué elegirnos?",
          "columnas": "3",
          "items": [
            {"icono": "🌱", "titulo": "Sostenible", "descripcion": "Productos locales y ecológicos"},
            {"icono": "👥", "titulo": "Comunidad", "descripcion": "Más de 500 familias"},
            {"icono": "💚", "titulo": "Ahorro", "descripcion": "Hasta 30% de ahorro"}
          ]
        },
        "styles": {
          "spacing": {"padding": {"top": "80px", "bottom": "80px"}},
          "colors": {"background": "#f8fafc"}
        }
      },
      {
        "id": "el_gc_catalogo",
        "type": "gc-catalogo",
        "data": {
          "titulo": "Productos Disponibles",
          "limite": 8,
          "columnas": 4,
          "mostrar_filtros": true,
          "esquema_color": "success"
        }
      },
      {
        "id": "el_eventos",
        "type": "eventos-proximos",
        "data": {
          "titulo": "Próximos Eventos",
          "limite": 3,
          "mostrar_calendario": false
        }
      },
      {
        "id": "el_cta_001",
        "type": "cta",
        "variant": "gradient",
        "data": {
          "titulo": "¿Listo para unirte?",
          "subtitulo": "Forma parte de nuestra comunidad hoy",
          "boton_texto": "Registrarse",
          "boton_url": "/registro",
          "color_fondo": "#22c55e"
        }
      }
    ],
    "settings": {
      "pageWidth": "1200",
      "backgroundColor": "#ffffff",
      "fullWidth": true
    }
  }'
```

---

## Flujo: Crear Página de Módulo

### Ejemplo: Dashboard de Grupos de Consumo

```bash
# Crear página con shortcode del módulo
curl -X POST "https://sitio.local/wp-json/wp/v2/pages" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mi Grupo de Consumo",
    "content": "[flavor_client_dashboard module=\"grupos_consumo\"]",
    "status": "publish",
    "template": "flavor-dashboard"
  }'
```

### Shortcodes de Dashboard por Módulo

| Módulo | Shortcode |
|--------|-----------|
| Grupos de Consumo | `[flavor_client_dashboard module="grupos_consumo"]` |
| Eventos | `[flavor_client_dashboard module="eventos"]` |
| Socios | `[flavor_client_dashboard module="socios"]` |
| Marketplace | `[flavor_client_dashboard module="marketplace"]` |
| Cursos | `[flavor_client_dashboard module="cursos"]` |
| Reservas | `[flavor_client_dashboard module="reservas"]` |
| Incidencias | `[flavor_client_dashboard module="incidencias"]` |
| Transparencia | `[flavor_client_dashboard module="transparencia"]` |

---

## Flujo: Crear Widget Personalizado para Módulo

### Paso 1: Registrar Widget en el Módulo

Editar el archivo del módulo (`class-{modulo}-module.php`):

```php
// En el método register_widgets() del módulo
public function register_widgets() {
    $registry = Flavor_Widget_Registry::get_instance();

    $registry->register_widget(array(
        'id'          => 'mi_modulo_widget_custom',
        'title'       => __('Mi Widget Personalizado', 'flavor-chat-ia'),
        'category'    => 'mi_modulo',
        'description' => 'Descripción del widget',
        'icon'        => '🎯',
        'render_callback' => array($this, 'render_widget_custom'),
        'settings'    => array(
            'limite'  => array('type' => 'number', 'default' => 5),
            'mostrar' => array('type' => 'boolean', 'default' => true)
        )
    ));
}

public function render_widget_custom($settings) {
    $limite = $settings['limite'] ?? 5;
    // Lógica del widget...
    return $html;
}
```

### Paso 2: Registrar Bloque en VBP

El widget se registra automáticamente en VBP si el módulo está activo. Para personalizar:

```php
// En class-vbp-block-library.php o mediante hook
add_action('vbp_register_blocks', function($library) {
    $library->registrar_bloque(array(
        'id'       => 'mi_modulo_custom',
        'name'     => 'Mi Widget Custom',
        'category' => 'modules',
        'module'   => 'mi_modulo',
        'shortcode'=> 'mi_modulo_custom',
        'icon'     => '🎯',
        'fields'   => array(
            'titulo'  => array('type' => 'text', 'label' => 'Título'),
            'limite'  => array('type' => 'number', 'label' => 'Límite', 'default' => 5),
            'columnas'=> array('type' => 'select', 'label' => 'Columnas', 'options' => array('2'=>'2','3'=>'3','4'=>'4'))
        )
    ));
});
```

### Paso 3: Crear Shortcode

```php
// En el módulo
add_shortcode('mi_modulo_custom', array($this, 'shortcode_custom'));

public function shortcode_custom($atts) {
    $atts = shortcode_atts(array(
        'titulo'   => '',
        'limite'   => 5,
        'columnas' => 3
    ), $atts);

    ob_start();
    // Renderizar widget
    include FLAVOR_MODULES_PATH . 'mi-modulo/templates/widget-custom.php';
    return ob_get_clean();
}
```

---

## Flujo: Crear Atajo (Shortcut) para Acción Común

### Ejemplo: Atajo para crear Landing de Módulo

```php
// En functions.php del tema o plugin
add_action('init', function() {
    // Registrar atajo via WP-CLI
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::add_command('flavor create-landing', function($args, $assoc_args) {
            $modulo = $assoc_args['module'] ?? 'grupos_consumo';
            $titulo = $assoc_args['title'] ?? 'Landing ' . ucfirst($modulo);

            // Crear landing
            $post_id = wp_insert_post(array(
                'post_title'  => $titulo,
                'post_type'   => 'flavor_landing',
                'post_status' => 'publish'
            ));

            // Aplicar template
            $template = Flavor_Landing_Builder::get_template_for_module($modulo);
            update_post_meta($post_id, '_vbp_document_data', $template);

            WP_CLI::success("Landing creada: ID $post_id");
        });
    }
});
```

**Uso:**
```bash
wp flavor create-landing --module=eventos --title="Eventos Comunitarios"
```

---

## Patrones de Diseño Recomendados

### Landing Page Estándar

```
┌─────────────────────────────────────┐
│             HERO                    │  ← fullscreen, gradient
├─────────────────────────────────────┤
│           FEATURES                  │  ← 3 columnas, cards
├─────────────────────────────────────┤
│      WIDGET PRINCIPAL MÓDULO        │  ← gc-catalogo, eventos, etc
├─────────────────────────────────────┤
│         TESTIMONIALS                │  ← carousel
├─────────────────────────────────────┤
│             STATS                   │  ← counters
├─────────────────────────────────────┤
│              FAQ                    │  ← accordion
├─────────────────────────────────────┤
│              CTA                    │  ← gradient, newsletter
└─────────────────────────────────────┘
```

### Dashboard de Usuario

```
┌─────────────────────────────────────┐
│         HEADER + NAV                │
├───────────┬─────────────────────────┤
│           │                         │
│  SIDEBAR  │    CONTENIDO MÓDULO     │
│  WIDGETS  │                         │
│           │                         │
├───────────┴─────────────────────────┤
│            FOOTER                   │
└─────────────────────────────────────┘
```

---

## Ejemplo Completo: Sitio de Grupo de Consumo

```bash
#!/bin/bash
# Script: crear-sitio-gc.sh

API_URL="https://misitio.local/wp-json"
AUTH="Authorization: Bearer $TOKEN"

# 1. Activar módulos
curl -X POST "$API_URL/flavor-site-builder/v1/modules/activate" \
  -H "$AUTH" -H "Content-Type: application/json" \
  -d '{"modules": ["grupos_consumo", "eventos", "socios", "marketplace"]}'

# 2. Crear sitio base
curl -X POST "$API_URL/flavor-site-builder/v1/site/create" \
  -H "$AUTH" -H "Content-Type: application/json" \
  -d '{
    "nombre_sitio": "EcoGrupo Local",
    "template": "grupo_consumo",
    "demo_data": true
  }'

# 3. Obtener ID de landing creada
LANDING_ID=$(curl -s "$API_URL/wp/v2/flavor_landing?slug=inicio" | jq '.[0].id')

# 4. Personalizar landing
curl -X POST "$API_URL/flavor-vbp/v1/documents/$LANDING_ID" \
  -H "$AUTH" -H "Content-Type: application/json" \
  -d @landing-personalizada.json

# 5. Crear menú personalizado
curl -X POST "$API_URL/flavor-site-builder/v1/menu" \
  -H "$AUTH" -H "Content-Type: application/json" \
  -d '{
    "name": "Menu Principal",
    "items": [
      {"title": "Inicio", "url": "/"},
      {"title": "Productos", "url": "/productos"},
      {"title": "Eventos", "url": "/eventos"},
      {"title": "Nosotros", "url": "/nosotros"}
    ]
  }'

echo "✅ Sitio creado correctamente"
```

---

## Validación y Errores

### Validar Configuración Antes de Aplicar

```bash
curl -X POST "$API_URL/flavor-site-builder/v1/site/validate" \
  -H "$AUTH" -H "Content-Type: application/json" \
  -d '{
    "template": "grupo_consumo",
    "modulos": ["grupos_consumo"]
  }'
```

**Respuesta de error:**
```json
{
  "valid": false,
  "errors": [
    {"code": "module_inactive", "message": "El módulo 'grupos_consumo' no está activo"},
    {"code": "missing_dependency", "message": "El módulo 'socios' es requerido"}
  ]
}
```

### Códigos de Error Comunes

| Código | Descripción |
|--------|-------------|
| `module_inactive` | Módulo no activado |
| `missing_dependency` | Falta módulo dependiente |
| `invalid_template` | Template no existe |
| `invalid_element` | Elemento VBP mal formado |
| `permission_denied` | Sin permisos suficientes |

---

## Recursos Adicionales

- **Referencia de Bloques:** `VBP-BLOCKS-REFERENCE.md`
- **API Endpoints:** `API-REFERENCE.md`
- **Shortcodes por Módulo:** `SHORTCODES-REFERENCE.md`
- **Presets y Templates:** `TEMPLATES-REFERENCE.md`
