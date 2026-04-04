# Flavor VBP MCP Server v2.1

Servidor MCP para integrar Visual Builder Pro con Claude Code. Permite crear y gestionar páginas VBP directamente desde terminal usando IA.

## Instalación

```bash
cd mcp-server
npm install
```

## Configuración en Claude Code

Añadir a `~/.claude/settings.json`:

```json
{
  "mcpServers": {
    "flavor-vbp": {
      "command": "node",
      "args": ["/ruta/a/flavor-chat-ia/mcp-server/index.js"],
      "env": {
        "SITE_URL": "http://sitio-prueba.local",
        "VBP_API_KEY": "<tu-api-key>"
      }
    }
  }
}
```

## Herramientas Disponibles

### Creación de Páginas

#### `vbp_create_page`
Crea una nueva página VBP con plantilla o elementos personalizados.

```javascript
// Con plantilla predefinida
vbp_create_page({
  title: "Mi Landing",
  template: "landing-completa",
  context: {
    topic: "App de Productividad",
    industry: "tech"  // tech, ecommerce, community, health, food
  }
})

// Con elementos personalizados
vbp_create_page({
  title: "Página Custom",
  elements: [/* array de secciones */]
})
```

**Plantillas disponibles:**
- `landing-basica` - Hero + Features + CTA
- `landing-completa` - Todas las secciones típicas
- `landing-producto` - Para productos/servicios
- `landing-startup` - Para startups
- `landing-saas` - Para software como servicio
- `grupos-consumo` - Con módulo de GC
- `eventos` - Con módulo de eventos
- `marketplace` - Con módulo marketplace
- `cursos` - Con módulo de cursos
- `comunidad` - Para comunidades
- `restaurante` - Para hostelería
- `clinica` - Para centros de salud
- `tienda` - E-commerce
- `servicios` - Servicios profesionales
- `app-movil` - Para apps móviles

**Industrias disponibles:**
- `tech` - Contenido para startups/SaaS
- `ecommerce` - Contenido para tiendas
- `community` - Contenido para comunidades
- `health` - Contenido para salud
- `food` - Contenido para restaurantes

### Generación de Secciones

#### `vbp_generate_section`
Genera una sección individual con contenido personalizable.

```javascript
vbp_generate_section({
  sectionType: "hero",
  context: {
    titulo: "Bienvenido a Nuestra Plataforma",
    subtitulo: "La mejor solución para tu negocio",
    boton_texto: "Empezar gratis"
  }
})
```

**Tipos de sección:**
- `hero` - Cabecera principal
- `features` - Características/beneficios
- `cta` - Call to action
- `testimonials` - Testimonios
- `faq` - Preguntas frecuentes
- `pricing` - Tabla de precios
- `team` - Equipo
- `contact` - Contacto
- `stats` - Estadísticas
- `gallery` - Galería
- `text` - Bloque de texto
- `module_grupos_consumo` - Widget de grupos de consumo
- `module_eventos` - Widget de eventos
- `module_marketplace` - Widget de marketplace
- `module_cursos` - Widget de cursos

### Gestión de Páginas

#### `vbp_list_pages`
Lista todas las páginas VBP existentes.

#### `vbp_get_page`
Obtiene los detalles de una página específica.

#### `vbp_update_page`
Actualiza título, elementos o estado de una página.

#### `vbp_add_block`
Añade un bloque a una página existente.

#### `vbp_duplicate_page` (NUEVO)
Duplica una página existente con todos sus elementos.

```javascript
vbp_duplicate_page({
  postId: 123,
  title: "Nueva copia"  // opcional
})
```

### Información

#### `vbp_list_blocks`
Lista todos los bloques disponibles por categoría.

#### `vbp_get_block_schema`
Obtiene el schema detallado de un bloque.

#### `vbp_list_modules`
Lista módulos Flavor activos.

#### `vbp_list_templates`
Lista plantillas de página disponibles.

#### `vbp_list_section_types` (NUEVO)
Lista tipos de sección que se pueden generar.

#### `vbp_get_block_presets` (NUEVO)
Obtiene los presets predefinidos de un bloque.

```javascript
vbp_get_block_presets({
  blockType: "hero"
})
// Retorna: startup, ecommerce, comunidad
```

## Ejemplos de Uso con Claude Code

### Crear landing para grupo de consumo
```
"Crea una landing page para un grupo de consumo ecológico llamado 'La Huerta Feliz'
que destaque productos locales y sostenibilidad"
```

### Crear landing de producto SaaS
```
"Crea una landing completa para una app de gestión de proyectos llamada 'TaskFlow'
con precios, testimonios y FAQs"
```

### Añadir sección a página existente
```
"Añade una sección de precios a la página 123 con 3 planes: básico, pro y enterprise"
```

### Duplicar página
```
"Duplica la página 123 con el título 'Nueva versión'"
```

## API REST

La API REST está disponible en:
- Base URL: `{site_url}/wp-json/flavor-vbp/v1/claude/`
- Autenticación: Header `X-VBP-Key: {api_key}` o query param `api_key`

### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/blocks` | Lista bloques |
| GET | `/blocks/{type}/presets` | Presets de un bloque |
| GET | `/schema` | Schema completo |
| GET | `/templates` | Lista plantillas |
| GET | `/section-types` | Tipos de sección |
| GET | `/modules` | Módulos activos |
| GET | `/pages` | Lista páginas |
| POST | `/pages` | Crear página |
| GET | `/pages/{id}` | Obtener página |
| PUT | `/pages/{id}` | Actualizar página |
| POST | `/pages/{id}/blocks` | Añadir bloque |
| POST | `/pages/{id}/duplicate` | Duplicar página |
| POST | `/generate-section` | Generar sección |

## Estructura de Elementos VBP

```json
{
  "id": "el_abc123xyz789",
  "type": "hero",
  "name": "Hero",
  "visible": true,
  "locked": false,
  "data": {
    "titulo": "...",
    "subtitulo": "...",
    "boton_texto": "..."
  },
  "styles": {
    "spacing": { "margin": {}, "padding": {} },
    "colors": { "background": "", "text": "" },
    "typography": {},
    "borders": {},
    "shadows": {},
    "layout": {},
    "advanced": { "cssId": "", "cssClasses": "", "customCss": "" }
  },
  "children": []
}
```

## Troubleshooting

### El servidor no conecta
1. Verifica que el sitio WordPress esté corriendo
2. Comprueba la URL en `SITE_URL`
3. Verifica que el plugin Flavor esté activo

### Error de permisos
- Asegúrate de usar la API key correcta
- Verifica que el usuario tenga permisos `edit_posts`

### Schema vacío
Ejecuta el script de exportación:
```
http://tu-sitio.local/wp-content/plugins/flavor-chat-ia/tools/export-vbp-schema.php?key=flavor2024
```

## Changelog

### v2.1.0
- Añadido endpoint `/templates` con plantillas completas
- Añadido endpoint `/section-types` para tipos de sección
- Añadido endpoint `/blocks/{type}/presets` para presets
- Añadido endpoint `/pages/{id}/duplicate` para duplicar
- Añadido soporte para `context` en creación de páginas
- Contenido personalizado por industria (tech, ecommerce, community, health, food)
- Corregido bug de type=null en respuesta de bloques
- Mejorada documentación CLAUDE_INSTRUCTIONS.md

### v2.0.0
- Migración de WP-CLI a HTTP API
- Plantillas de sección predefinidas
- Plantillas de página completas
- Soporte para módulos Flavor
