# Instrucciones para Claude Code - VBP Integration v2.1

Este archivo contiene instrucciones para que Claude Code pueda crear páginas VBP de forma efectiva.

## Contexto del Sistema

Visual Builder Pro (VBP) es un editor visual tipo Figma/Photoshop para WordPress.
Las páginas se componen de **elementos** (secciones) que se apilan verticalmente.
Cada elemento tiene un **type**, **data** (contenido) y **styles** (diseño).

## Herramientas Disponibles

### Creación y Gestión de Páginas

#### `vbp_create_page`
Crea una nueva página VBP con plantilla o elementos personalizados.

```javascript
// Con plantilla predefinida (recomendado)
vbp_create_page({
  title: "Mi Landing SaaS",
  template: "landing-saas",
  context: {
    topic: "TaskFlow App",
    industry: "tech"  // tech, ecommerce, community, health, food
  }
})

// Con elementos personalizados
vbp_create_page({
  title: "Página Custom",
  elements: [/* array de secciones */]
})
```

#### `vbp_get_page`
Obtiene detalles de una página existente.

#### `vbp_update_page`
Actualiza título, elementos o estado de una página.

#### `vbp_list_pages`
Lista todas las páginas VBP existentes.

#### `vbp_duplicate_page`
Duplica una página existente.

```javascript
vbp_duplicate_page({
  postId: 123,
  title: "Nueva copia"  // opcional
})
```

### Generación de Secciones

#### `vbp_generate_section`
Genera una sección individual con contenido personalizable.

```javascript
vbp_generate_section({
  sectionType: "hero",
  context: {
    titulo: "Bienvenido a Nuestra Plataforma",
    subtitulo: "La mejor solución para tu negocio",
    boton_texto: "Empezar gratis",
    industry: "tech"
  }
})
```

#### `vbp_add_block`
Añade un bloque a una página existente.

```javascript
vbp_add_block({
  postId: 123,
  blockType: "pricing",
  data: { titulo: "Nuestros Planes" },
  position: "end"  // start, end, o índice numérico
})
```

### Información

#### `vbp_list_blocks`
Lista todos los bloques disponibles por categoría.

#### `vbp_get_block_schema`
Obtiene el schema detallado de un bloque.

#### `vbp_list_templates`
Lista plantillas de página disponibles.

#### `vbp_list_section_types`
Lista tipos de sección que se pueden generar.

#### `vbp_get_block_presets`
Obtiene presets predefinidos de un bloque.

#### `vbp_list_modules`
Lista módulos Flavor activos.

## Plantillas de Página Disponibles

| Plantilla | Industria | Secciones |
|-----------|-----------|-----------|
| `landing-basica` | general | hero, features, cta |
| `landing-completa` | tech | hero, features, stats, testimonials, pricing, faq, cta |
| `landing-producto` | tech | hero, features, gallery, testimonials, pricing, cta |
| `landing-startup` | tech | hero, stats, features, team, testimonials, pricing, faq, cta |
| `landing-saas` | tech | hero, features, stats, pricing, testimonials, faq, cta |
| `grupos-consumo` | community | hero, module_grupos_consumo, features, testimonials, faq, cta |
| `eventos` | community | hero, module_eventos, features, cta |
| `marketplace` | ecommerce | hero, module_marketplace, features, testimonials, cta |
| `cursos` | tech | hero, module_cursos, features, testimonials, faq, cta |
| `comunidad` | community | hero, features, stats, team, testimonials, cta |
| `restaurante` | food | hero, features, gallery, testimonials, contact |
| `clinica` | health | hero, features, team, testimonials, faq, contact |
| `tienda` | ecommerce | hero, features, testimonials, faq, cta |
| `servicios` | general | hero, features, stats, testimonials, pricing, contact |
| `app-movil` | tech | hero, features, stats, testimonials, faq, cta |

## Industrias y su Contenido

Las industrias personalizan automáticamente:
- Textos y CTAs apropiados
- Features relevantes
- Testimonios de ejemplo
- FAQs típicas
- Planes de precio (si aplica)

**Industrias disponibles:**
- `tech` - Startups, SaaS, apps
- `ecommerce` - Tiendas online
- `community` - Asociaciones, cooperativas
- `health` - Clínicas, salud
- `food` - Restaurantes, hostelería
- `general` - Por defecto

## Tipos de Sección

### Secciones principales
| Tipo | Descripción |
|------|-------------|
| `hero` | Cabecera principal con CTA |
| `features` | Grid de características/beneficios |
| `cta` | Llamada a la acción |
| `testimonials` | Testimonios de clientes |
| `faq` | Preguntas frecuentes |
| `pricing` | Tabla de precios |
| `stats` | Estadísticas/métricas |
| `team` | Presentación del equipo |
| `contact` | Formulario e info de contacto |
| `gallery` | Galería de imágenes |
| `text` | Bloque de texto libre |

### Widgets de Módulos
| Tipo | Descripción |
|------|-------------|
| `module_grupos_consumo` | Listado de grupos de consumo |
| `module_eventos` | Próximos eventos |
| `module_marketplace` | Productos del marketplace |
| `module_cursos` | Catálogo de cursos |

## Estructura de Elementos VBP

```json
{
  "id": "el_abc123xyz789",
  "type": "hero",
  "name": "Hero",
  "visible": true,
  "locked": false,
  "data": {
    "titulo": "Título principal",
    "subtitulo": "Subtítulo descriptivo",
    "boton_texto": "Acción",
    "boton_url": "#contacto"
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

## Ejemplos de Uso

### Landing para startup tech
```javascript
vbp_create_page({
  title: "TaskFlow - Gestión de Proyectos",
  template: "landing-saas",
  context: {
    topic: "TaskFlow",
    industry: "tech"
  }
})
```

### Landing para grupo de consumo
```javascript
vbp_create_page({
  title: "La Huerta Feliz",
  template: "grupos-consumo",
  context: {
    topic: "La Huerta Feliz - Consumo Ecológico",
    industry: "community"
  }
})
```

### Añadir sección de precios personalizada
```javascript
vbp_add_block({
  postId: 123,
  blockType: "pricing",
  data: {
    titulo: "Elige tu plan",
    planes: [
      { nombre: "Básico", precio: 9, caracteristicas: "3 usuarios\n10GB" },
      { nombre: "Pro", precio: 29, destacado: true, caracteristicas: "Ilimitado\n100GB\nSoporte 24/7" }
    ]
  }
})
```

## Flujo de Trabajo Recomendado

1. **Para landings genéricas**: Usar `template` con `context` apropiado
2. **Para páginas personalizadas**: Generar secciones con `vbp_generate_section` y combinar
3. **Para modificaciones**: Usar `vbp_add_block` o `vbp_update_page`
4. **Para duplicar**: Usar `vbp_duplicate_page`

## URLs de Resultado

Después de crear/modificar una página:
- **Editor VBP**: `{admin_url}/admin.php?page=vbp-editor&post_id={id}`
- **Vista previa**: `{site_url}/?p={id}&preview=true`
