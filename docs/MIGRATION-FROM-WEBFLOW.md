# Migracion de Webflow a VBP

Guia para migrar sitios de Webflow a WordPress con Visual Builder Pro.

---

## Indice

1. [Por que Migrar de Webflow](#por-que-migrar-de-webflow)
2. [Diferencias de Plataforma](#diferencias-de-plataforma)
3. [Planificacion de la Migracion](#planificacion-de-la-migracion)
4. [Exportar desde Webflow](#exportar-desde-webflow)
5. [Preparar WordPress + VBP](#preparar-wordpress--vbp)
6. [Migrar Estructura y Diseno](#migrar-estructura-y-diseno)
7. [Migrar CMS Content](#migrar-cms-content)
8. [Migrar Interacciones](#migrar-interacciones)
9. [Migrar Formularios](#migrar-formularios)
10. [SEO y Redirecciones](#seo-y-redirecciones)
11. [Verificacion y Launch](#verificacion-y-launch)

---

## Por que Migrar de Webflow

### Razones comunes para migrar

| Razon | Descripcion |
|-------|-------------|
| **Coste** | Webflow se vuelve caro al escalar |
| **Control** | Sin acceso a servidor/base de datos |
| **Lock-in** | Contenido atrapado en plataforma |
| **Funcionalidad** | WordPress tiene mas plugins/integraciones |
| **CMS limitado** | Webflow CMS es basico para proyectos grandes |
| **Ecommerce** | WooCommerce es mas potente que Webflow Ecommerce |

### Comparativa de costes

| Escenario | Webflow/mes | WordPress + VBP/mes |
|-----------|-------------|---------------------|
| Blog simple | $14 | $5-10 |
| Sitio business | $39 | $10-20 |
| CMS 10k items | $39 | $15-30 |
| CMS 100k items | No disponible | $20-50 |
| Ecommerce basico | $29 | $15-30 |
| Ecommerce avanzado | $212+ | $30-60 |

### Que se gana

| Caracteristica | Webflow | WordPress + VBP |
|----------------|---------|-----------------|
| Self-hosted | No | Si |
| Plugins ilimitados | No | Si |
| CMS items | 10,000 max | Ilimitado |
| Ecommerce | Limitado | WooCommerce completo |
| Multi-idioma | Limitado | Nativo |
| Backup local | No | Si |
| Colaboracion real-time | No | Si |
| Branching | No | Si |
| Modo offline | No | Si |

---

## Diferencias de Plataforma

### Arquitectura

```
WEBFLOW:
+------------------+
| Webflow SaaS     |  <- Todo en su nube
+--------+---------+
         |
+--------v---------+
| Designer (editor)|  <- Propietario
+--------+---------+
         |
+--------v---------+
| Hosting incluido |  <- Sin control
+------------------+

WORDPRESS + VBP:
+------------------+
| Tu servidor      |  <- Control total
+--------+---------+
         |
+--------v---------+
| WordPress CMS    |  <- Open source
+--------+---------+
         |
+--------v---------+
| VBP (builder)    |  <- Diseno profesional
+------------------+
```

### Mapeo de conceptos

| Webflow | WordPress + VBP |
|---------|-----------------|
| Site | WordPress site |
| Page | Page |
| Collection | Custom Post Type |
| Collection item | Post |
| CMS Template | Template part |
| Symbol | VBP Symbol |
| Component | VBP Component |
| Class | CSS class |
| Combo class | Variante |
| Interaction | VBP Animation |
| Form | Form block / plugin |

---

## Planificacion de la Migracion

### 1. Inventario de Webflow

Documentar todo lo que tienes:

```markdown
## Inventario del sitio Webflow

### Paginas estaticas
- [ ] Home
- [ ] About
- [ ] Services
- [ ] Contact
- [ ] ...

### Collections (CMS)
- [ ] Blog posts: X items
- [ ] Team members: X items
- [ ] Projects: X items
- [ ] ...

### Symbols
- [ ] Navbar
- [ ] Footer
- [ ] Card component
- [ ] ...

### Forms
- [ ] Contact form
- [ ] Newsletter signup
- [ ] ...

### Interactions
- [ ] Hero animations
- [ ] Scroll effects
- [ ] Hover states
- [ ] ...

### Integraciones
- [ ] Analytics
- [ ] Mailchimp
- [ ] Zapier
- [ ] ...
```

### 2. Exportar assets

```bash
# Desde Webflow Designer:
# 1. Ir a Project Settings > Custom Code
# 2. Exportar CSS si tienes plan paid

# Para imagenes:
# 1. Ir a Assets panel
# 2. Descargar todas las imagenes

# O usar herramienta de scraping:
wget -r -np -k http://tu-sitio.webflow.io/
```

### 3. Timeline estimado

| Tamano del sitio | Tiempo estimado |
|------------------|-----------------|
| Landing simple (5 paginas) | 1-2 dias |
| Sitio mediano (20 paginas) | 1 semana |
| Sitio con CMS (50+ items) | 2-3 semanas |
| Ecommerce | 3-4 semanas |

---

## Exportar desde Webflow

### 1. Exportar codigo (plan paid)

```bash
# 1. En Webflow Dashboard
# 2. Project Settings > Code Export
# 3. Download ZIP

# El ZIP contiene:
# - HTML files
# - CSS (minificado)
# - JS (interacciones)
# - Images
```

### 2. Exportar contenido CMS

```bash
# 1. En Webflow Dashboard
# 2. CMS > Collection > Export
# 3. Download CSV

# Hacer para cada Collection
```

### 3. Extraer estructura de paginas

Si no tienes plan paid, puedes analizar el sitio publicado:

```bash
#!/bin/bash
# scrape-webflow-structure.sh

SITE_URL="https://tu-sitio.webflow.io"

# Extraer HTML de cada pagina
curl -s "$SITE_URL" > home.html
curl -s "$SITE_URL/about" > about.html
# etc...

# Extraer clases CSS usadas
grep -oP 'class="[^"]*"' home.html | sort -u > classes.txt
```

---

## Preparar WordPress + VBP

### 1. Instalar WordPress

```bash
# Con Local by Flywheel, MAMP, o servidor real
# Verificar instalacion
wp core version
```

### 2. Instalar Flavor Platform

```bash
wp plugin install flavor-platform --activate
wp plugin is-active flavor-platform
```

### 3. Configurar tema

```bash
# Usar tema compatible
wp theme activate flavor-starter
```

### 4. Obtener API key

```bash
API_KEY=$(wp eval 'echo flavor_get_vbp_api_key();')
echo "API Key: $API_KEY"
```

### 5. Ejecutar inventario VBP

```bash
bash tools/vbp-inventory.sh "http://tu-nuevo-sitio.local"
```

---

## Migrar Estructura y Diseno

### 1. Crear design tokens

Extraer de Webflow CSS y crear tokens:

```bash
# Analizar CSS exportado para colores
grep -oP '#[a-fA-F0-9]{6}' styles.css | sort -u

# Crear tokens en VBP
curl -X POST "http://sitio.local/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "colors": {
      "primary": "#1a73e8",
      "secondary": "#34a853",
      "dark": "#1f2937",
      "light": "#f3f4f6"
    },
    "typography": {
      "font-family": "Inter, sans-serif",
      "heading-family": "Poppins, sans-serif"
    }
  }'
```

### 2. Recrear layout principal

```bash
# Navbar como Symbol/Template
curl -X POST "http://sitio.local/wp-json/flavor-vbp/v1/claude/templates" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Navbar",
    "type": "header",
    "blocks": [
      {
        "type": "header",
        "props": {"sticky": true},
        "children": [
          {
            "type": "container",
            "props": {"maxWidth": "1200px", "layout": "flex", "justify": "space-between"},
            "children": [
              {"type": "logo"},
              {"type": "nav-menu", "props": {"menu": "primary"}},
              {"type": "button", "props": {"text": "Get Started", "style": "primary"}}
            ]
          }
        ]
      }
    ]
  }'
```

### 3. Mapeo de clases Webflow a VBP

| Webflow Class | VBP Equivalente |
|---------------|-----------------|
| `.container` | `container` block |
| `.hero` | `section` con props |
| `.grid` | `columns` block |
| `.button` | `button` block |
| `.heading-1` | `heading` level 1 |
| `.text-block` | `text` block |
| `.image` | `image` block |
| `.div-block` | `container` block |
| `.section` | `section` block |

### 4. Recrear paginas

```bash
# Ejemplo: Recrear homepage
curl -X POST "http://sitio.local/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Home",
    "slug": "home",
    "set_as_homepage": true,
    "content": {
      "blocks": [
        {
          "type": "section",
          "props": {
            "className": "hero",
            "background": {"type": "gradient", "from": "#1a73e8", "to": "#4285f4"}
          },
          "children": [
            {
              "type": "container",
              "props": {"maxWidth": "800px", "textAlign": "center"},
              "children": [
                {
                  "type": "heading",
                  "props": {"level": 1, "text": "Welcome to Our Site", "color": "#fff"}
                },
                {
                  "type": "text",
                  "props": {"content": "Your tagline here", "color": "#fff"}
                },
                {
                  "type": "button",
                  "props": {"text": "Get Started", "url": "/signup", "style": "white"}
                }
              ]
            }
          ]
        }
      ]
    }
  }'
```

---

## Migrar CMS Content

### 1. Crear Custom Post Types

Para cada Collection de Webflow:

```php
<?php
// En functions.php o plugin custom

// Ejemplo: Collection "Projects"
register_post_type( 'project', [
    'labels' => [
        'name' => 'Projects',
        'singular_name' => 'Project',
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
    'show_in_rest' => true,
] );

// O con ACF/Meta Box para campos custom
```

### 2. Importar desde CSV

```php
<?php
/**
 * Importador de Webflow CSV
 * wp eval-file import-webflow-csv.php projects.csv project
 */

$csv_file = $argv[1] ?? 'export.csv';
$post_type = $argv[2] ?? 'post';

$handle = fopen( $csv_file, 'r' );
$headers = fgetcsv( $handle );

while ( ( $row = fgetcsv( $handle ) ) !== false ) {
    $data = array_combine( $headers, $row );

    // Mapear campos de Webflow a WordPress
    $post_data = [
        'post_title'   => $data['Name'] ?? $data['Title'] ?? 'Untitled',
        'post_content' => $data['Description'] ?? $data['Content'] ?? '',
        'post_type'    => $post_type,
        'post_status'  => 'publish',
    ];

    $post_id = wp_insert_post( $post_data );

    // Campos custom
    if ( isset( $data['Slug'] ) ) {
        wp_update_post( ['ID' => $post_id, 'post_name' => $data['Slug']] );
    }

    // Imagen destacada (si existe URL)
    if ( ! empty( $data['Featured Image'] ) ) {
        $image_url = $data['Featured Image'];
        // Descargar e importar imagen...
        // (codigo de importacion de imagen)
    }

    // Meta fields adicionales
    foreach ( $data as $key => $value ) {
        if ( ! in_array( $key, ['Name', 'Title', 'Description', 'Content', 'Slug'] ) ) {
            update_post_meta( $post_id, sanitize_key( $key ), $value );
        }
    }

    echo "Importado: {$post_data['post_title']}\n";
}

fclose( $handle );
```

### 3. Script automatizado

```bash
#!/bin/bash
# import-webflow-collections.sh

SITE_URL=$1
API_KEY=$2
CSV_DIR=$3

# Importar cada collection
for csv in $CSV_DIR/*.csv; do
    collection=$(basename "$csv" .csv)
    echo "Importando: $collection"

    # Crear CPT si no existe
    wp eval "
        if ( ! post_type_exists( '$collection' ) ) {
            register_post_type( '$collection', [
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            ] );
        }
    "

    # Importar contenido
    wp eval-file import-webflow-csv.php "$csv" "$collection"
done

echo "Importacion completada"
```

---

## Migrar Interacciones

### Mapeo de interacciones Webflow a VBP

| Webflow Interaction | VBP Animation |
|---------------------|---------------|
| On Load | `animation.trigger: "load"` |
| On Scroll | Scroll Animation |
| On Hover | `animation.trigger: "hover"` |
| On Click | `animation.trigger: "click"` |
| While Scrolling | Scroll-linked animation |
| Mouse Move | Parallax effect |

### 1. Animaciones de entrada

```json
// Webflow: "Move" on page load
// VBP equivalente:
{
  "type": "heading",
  "props": {
    "text": "Welcome",
    "animation": {
      "type": "fade-in-up",
      "trigger": "load",
      "duration": 800,
      "delay": 200,
      "easing": "ease-out"
    }
  }
}
```

### 2. Scroll animations

```json
// VBP Scroll Animation
{
  "type": "section",
  "props": {
    "scrollAnimation": {
      "type": "fade-in",
      "start": "top 80%",
      "end": "top 20%",
      "scrub": false
    }
  }
}
```

### 3. Parallax effects

```json
{
  "type": "image",
  "props": {
    "src": "/hero-bg.jpg",
    "parallax": {
      "speed": 0.5,
      "direction": "vertical"
    }
  }
}
```

### 4. Hover interactions

```json
{
  "type": "card",
  "props": {
    "hoverAnimation": {
      "scale": 1.05,
      "shadow": "0 20px 40px rgba(0,0,0,0.1)",
      "duration": 300
    }
  }
}
```

---

## Migrar Formularios

### Webflow Forms -> VBP Forms

```json
// VBP Form block
{
  "type": "form",
  "props": {
    "action": "/wp-json/flavor/v1/forms/submit",
    "method": "POST",
    "fields": [
      {
        "type": "text",
        "name": "name",
        "label": "Name",
        "placeholder": "Your name",
        "required": true
      },
      {
        "type": "email",
        "name": "email",
        "label": "Email",
        "placeholder": "your@email.com",
        "required": true
      },
      {
        "type": "textarea",
        "name": "message",
        "label": "Message",
        "rows": 5
      }
    ],
    "submitText": "Send Message",
    "successMessage": "Thank you! We'll be in touch.",
    "errorMessage": "Something went wrong. Please try again."
  }
}
```

### Integraciones de formularios

```bash
# Conectar con Mailchimp
curl -X POST "http://sitio.local/wp-json/flavor/v1/forms/integrations" \
  -H "X-VBP-Key: $API_KEY" \
  -d '{
    "form_id": "contact-form",
    "integration": "mailchimp",
    "settings": {
      "api_key": "xxx",
      "list_id": "xxx",
      "merge_fields": {
        "FNAME": "{name}"
      }
    }
  }'
```

---

## SEO y Redirecciones

### 1. Mapear URLs

Crear archivo de redirecciones:

```bash
# redirects.txt
# Formato: /old-url /new-url 301

/old-page /new-page 301
/blog/old-post /blog/new-post 301
/collections/project-slug /projects/project-slug 301
```

### 2. Implementar redirecciones

```php
<?php
// En functions.php o plugin

add_action( 'template_redirect', function() {
    $redirects = [
        '/old-page' => '/new-page',
        '/collections/projects' => '/projects',
        // ...
    ];

    $current = $_SERVER['REQUEST_URI'];

    foreach ( $redirects as $from => $to ) {
        if ( strpos( $current, $from ) === 0 ) {
            wp_redirect( home_url( $to ), 301 );
            exit;
        }
    }
} );
```

### 3. Migrar meta SEO

```bash
# Exportar meta de Webflow (si usabas SEO fields)
# E importar a Yoast/Rank Math

# Ejemplo con WP-CLI y Rank Math
wp eval "
    \$pages = [
        ['slug' => 'about', 'title' => 'About Us | Company', 'desc' => 'Learn about...'],
        ['slug' => 'services', 'title' => 'Our Services | Company', 'desc' => 'We offer...'],
    ];

    foreach ( \$pages as \$page ) {
        \$post = get_page_by_path( \$page['slug'] );
        if ( \$post ) {
            update_post_meta( \$post->ID, 'rank_math_title', \$page['title'] );
            update_post_meta( \$post->ID, 'rank_math_description', \$page['desc'] );
        }
    }
"
```

### 4. Configurar sitemap

```bash
# VBP genera sitemap automatico
# Verificar en: /sitemap.xml

# O usar Yoast/Rank Math para sitemap avanzado
wp plugin install wordpress-seo --activate
```

---

## Verificacion y Launch

### 1. Checklist pre-launch

```markdown
## Pre-Launch Checklist

### Contenido
- [ ] Todas las paginas migradas
- [ ] Todo el contenido CMS importado
- [ ] Imagenes funcionando
- [ ] Links internos actualizados
- [ ] Forms testeados

### Diseno
- [ ] Responsive mobile/tablet
- [ ] Tipografias correctas
- [ ] Colores consistentes
- [ ] Animaciones funcionando
- [ ] Header/footer correctos

### Tecnico
- [ ] SSL configurado
- [ ] Redirecciones activas
- [ ] 404 page configurada
- [ ] Sitemap generado
- [ ] robots.txt correcto
- [ ] Analytics instalado

### Performance
- [ ] Core Web Vitals OK
- [ ] Imagenes optimizadas
- [ ] Cache configurado
- [ ] CDN (si aplica)

### SEO
- [ ] Meta titles/descriptions
- [ ] Open Graph tags
- [ ] Schema markup
- [ ] Canonical URLs
```

### 2. Test de performance

```bash
# Comparar con sitio Webflow original
# Webflow
lighthouse https://tu-sitio.webflow.io --output=json > webflow.json

# Nuevo WordPress + VBP
lighthouse https://tu-nuevo-sitio.com --output=json > vbp.json

# Comparar scores
echo "Webflow Performance:"
jq '.categories.performance.score' webflow.json

echo "VBP Performance:"
jq '.categories.performance.score' vbp.json
```

### 3. Cambiar DNS

```bash
# 1. En tu registrador de dominio
# 2. Cambiar A record al nuevo servidor
# 3. Esperar propagacion (hasta 48h)

# Verificar propagacion
dig tu-dominio.com +short
```

### 4. Monitorear post-launch

```bash
# Verificar uptime
curl -I https://tu-sitio.com | head -1

# Verificar redirecciones
curl -I https://tu-sitio.com/old-page

# Verificar 404s en logs
tail -f /var/log/nginx/access.log | grep 404
```

---

## Ventajas Post-Migracion

### Lo que ganas

| Antes (Webflow) | Despues (VBP) |
|-----------------|---------------|
| $39-212/mes | $10-30/mes |
| 10k CMS items max | Ilimitado |
| Hosting fijo | Escala libre |
| Sin control servidor | Control total |
| Sin offline | Modo offline |
| Sin branching | Branching completo |
| Colaboracion limitada | Real-time |
| Integraciones limitadas | 60,000+ plugins |

### Proximos pasos recomendados

1. **Explorar modulos VBP** - Hay funcionalidades que Webflow no tiene
2. **Configurar colaboracion** - Invitar equipo al editor real-time
3. **Usar branching** - Para features y redisenos
4. **Considerar apps moviles** - Flavor Platform incluye apps Flutter
5. **Explorar automatizacion** - API + Claude Code para tareas repetitivas

---

## FAQ

### P: Puedo mantener el mismo dominio?

**R:** Si. Solo cambia los DNS records al nuevo servidor.

### P: Que pasa con los backlinks?

**R:** Configura redirecciones 301 para mantener el "link juice".

### P: El sitio sera mas rapido?

**R:** Generalmente si. VBP produce output mas ligero y tienes control sobre hosting.

### P: Puedo migrar Webflow Ecommerce?

**R:** Si, usando WooCommerce. Es mas potente pero requiere mas configuracion.

### P: Necesito el plan paid de Webflow para exportar?

**R:** Para exportar codigo limpio, si. Sin el, puedes hacer scraping o recrear manualmente.

### P: Cuanto cuesta el hosting WordPress?

**R:** Desde $5/mes en hosts basicos hasta $50+/mes en hosts premium. Mucho menos que Webflow en la mayoria de casos.

---

## Recursos

- [Documentacion VBP](./vbp/README.md)
- [API Reference](./vbp/api/rest-endpoints.md)
- [Matriz competitiva](./COMPETITIVE-MATRIX.md)
- [Casos de uso](./USE-CASES.md)

---

*Guia actualizada: Abril 2026*
*Version: 1.0*
