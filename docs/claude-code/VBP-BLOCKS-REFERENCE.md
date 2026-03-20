# Visual Builder Pro - Referencia de Bloques

Guía completa de bloques disponibles para crear páginas con el Visual Builder Pro.

## Estructura de un Elemento VBP

```json
{
  "id": "el_abc123xyz",
  "type": "hero",
  "variant": "fullscreen",
  "visible": true,
  "data": {
    "titulo": "Mi Título",
    "subtitulo": "Mi subtítulo",
    "boton_texto": "Acción"
  },
  "styles": {
    "spacing": {
      "margin": { "top": "", "right": "", "bottom": "", "left": "" },
      "padding": { "top": "80px", "right": "", "bottom": "80px", "left": "" }
    },
    "colors": { "background": "#1a1a2e", "text": "#ffffff" },
    "typography": { "fontSize": "", "fontWeight": "", "lineHeight": "", "textAlign": "center" }
  },
  "children": []
}
```

---

## Categorías de Bloques

| Categoría | Descripción | Bloques |
|-----------|-------------|---------|
| `sections` | Secciones completas | hero, features, testimonials, pricing, faq, cta, contact, gallery, stats, team |
| `basic` | Elementos básicos | heading, paragraph, button, icon, badge, alert, list |
| `layout` | Estructura de página | container, columns, row, grid, spacer, divider |
| `forms` | Formularios | form, input, textarea, select, checkbox, radio |
| `media` | Contenido multimedia | image, video, audio, gallery, slider |
| `modules` | Widgets de módulos | 150+ widgets de los 64 módulos |

---

## SECCIONES

### Hero
Sección principal de cabecera con título, subtítulo y botones de acción.

**Tipo:** `hero`

**Variantes:**
- `fullscreen` - Pantalla completa
- `split` - Dividido (imagen + contenido)
- `centered` - Contenido centrado
- `video` - Con video de fondo
- `slider` - Carrusel de slides
- `glassmorphism` - Efecto cristal
- `gradient` - Gradiente animado
- `parallax` - Efecto parallax
- `particles` - Con partículas
- `minimal` - Minimalista
- `3d` - Efecto 3D
- `typewriter` - Efecto máquina de escribir

**Campos de datos:**
```json
{
  "titulo": "string - Título principal",
  "subtitulo": "string - Subtítulo descriptivo",
  "boton_texto": "string - Texto del botón CTA",
  "boton_url": "string - URL del botón",
  "boton_2_texto": "string - Segundo botón (opcional)",
  "boton_2_url": "string - URL segundo botón",
  "imagen_fondo": "url - Imagen de fondo",
  "video_url": "url - Video de fondo (YouTube/Vimeo)",
  "color_fondo": "#hex - Color de fondo",
  "overlay_color": "#hex - Color del overlay",
  "overlay_opacity": "number 0-100 - Opacidad del overlay",
  "altura": "auto|50vh|75vh|100vh",
  "alineacion": "left|center|right",
  "titulo_color": "#hex",
  "subtitulo_color": "#hex",
  "boton_color_fondo": "#hex",
  "boton_color_texto": "#hex"
}
```

**Ejemplo:**
```json
{
  "type": "hero",
  "variant": "fullscreen",
  "data": {
    "titulo": "Bienvenido a nuestra plataforma",
    "subtitulo": "La mejor solución para tu comunidad",
    "boton_texto": "Comenzar ahora",
    "boton_url": "/registro",
    "imagen_fondo": "https://example.com/hero-bg.jpg",
    "overlay_color": "#000000",
    "overlay_opacity": 40,
    "altura": "100vh",
    "alineacion": "center"
  }
}
```

---

### Features
Sección de características con iconos y descripciones.

**Tipo:** `features`

**Variantes:**
- `grid` - Cuadrícula
- `list` - Lista vertical
- `alternating` - Alternado izquierda/derecha
- `cards` - Tarjetas elevadas
- `zigzag` - Zigzag
- `timeline` - Línea de tiempo
- `tabs` - Pestañas
- `accordion` - Acordeón
- `icons-only` - Solo iconos
- `bento` - Bento Grid (estilo Apple)
- `hover-cards` - Tarjetas con efecto hover

**Campos de datos:**
```json
{
  "titulo": "string - Título de la sección",
  "subtitulo": "string - Subtítulo",
  "columnas": "2|3|4 - Número de columnas",
  "items": [
    {
      "icono": "string - Nombre del icono (ej: rocket, shield, star)",
      "titulo": "string - Título del item",
      "descripcion": "string - Descripción",
      "enlace": "url - Enlace opcional"
    }
  ]
}
```

**Ejemplo:**
```json
{
  "type": "features",
  "variant": "cards",
  "data": {
    "titulo": "Nuestras Características",
    "subtitulo": "Todo lo que necesitas en un solo lugar",
    "columnas": "3",
    "items": [
      {
        "icono": "🚀",
        "titulo": "Rápido",
        "descripcion": "Rendimiento optimizado para tu comunidad"
      },
      {
        "icono": "🔒",
        "titulo": "Seguro",
        "descripcion": "Protección de datos de nivel empresarial"
      },
      {
        "icono": "🌱",
        "titulo": "Sostenible",
        "descripcion": "Diseñado para comunidades ecológicas"
      }
    ]
  }
}
```

---

### Testimonials
Sección de testimonios de usuarios/clientes.

**Tipo:** `testimonials`

**Variantes:**
- `carousel` - Carrusel
- `grid` - Cuadrícula
- `single` - Único destacado
- `masonry` - Masonry layout
- `video` - Video testimonios
- `rating` - Con estrellas
- `avatar-large` - Avatar grande
- `quote-card` - Tarjeta con cita
- `logos` - Con logos de empresas
- `twitter` - Estilo Twitter/X

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "autoplay": "boolean",
  "mostrar_rating": "boolean",
  "testimonios": [
    {
      "texto": "string - El testimonio",
      "nombre": "string - Nombre de la persona",
      "cargo": "string - Cargo/puesto",
      "empresa": "string - Empresa",
      "avatar": "url - Foto de perfil",
      "logo": "url - Logo de la empresa",
      "rating": "3|4|5 - Estrellas",
      "video_url": "url - Video opcional"
    }
  ]
}
```

---

### Pricing
Tabla de precios con planes y características.

**Tipo:** `pricing`

**Variantes:**
- `cards` - Tarjetas
- `table` - Tabla comparativa
- `toggle` - Toggle mensual/anual
- `comparison` - Comparación lado a lado
- `slider` - Slider de planes
- `minimal` - Minimalista
- `gradient` - Gradiente destacado
- `enterprise` - Enterprise con contacto
- `freemium` - Freemium destacado
- `horizontal` - Horizontal

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "moneda": "€|$|£",
  "toggle_activo": "boolean - Mostrar toggle mensual/anual",
  "descuento_anual": "number - % descuento anual",
  "planes": [
    {
      "nombre": "string - Nombre del plan",
      "precio_mensual": "number",
      "precio_anual": "number",
      "descripcion": "string",
      "destacado": "boolean - Resaltar este plan",
      "etiqueta": "string - Ej: 'Más popular'",
      "boton_texto": "string",
      "boton_url": "url",
      "caracteristicas": ["string - Lista de características"]
    }
  ]
}
```

---

### FAQ
Sección de preguntas frecuentes.

**Tipo:** `faq`

**Variantes:**
- `accordion` - Acordeón (default)
- `tabs` - Pestañas por categoría
- `cards` - Tarjetas expandibles
- `two-columns` - Dos columnas
- `search` - Con buscador
- `numbered` - Numeradas
- `timeline` - Línea de tiempo

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "columnas": "1|2",
  "expandir_primero": "boolean",
  "items": [
    {
      "pregunta": "string",
      "respuesta": "string - Soporta HTML básico"
    }
  ]
}
```

---

### CTA (Call to Action)
Sección de llamada a la acción.

**Tipo:** `cta`

**Variantes:**
- `centered` - Centrado
- `split` - Dividido
- `banner` - Banner horizontal
- `floating` - Flotante/sticky
- `newsletter` - Con formulario de email
- `gradient` - Con gradiente
- `image` - Con imagen
- `countdown` - Con cuenta atrás
- `video` - Con video de fondo

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "boton_texto": "string",
  "boton_url": "url",
  "boton_2_texto": "string",
  "boton_2_url": "url",
  "imagen": "url",
  "color_fondo": "#hex",
  "mostrar_formulario": "boolean",
  "placeholder_email": "string"
}
```

---

### Stats
Sección de estadísticas numéricas.

**Tipo:** `stats`

**Variantes:**
- `counters` - Contadores animados
- `cards` - Tarjetas
- `inline` - En línea
- `icons` - Con iconos
- `progress` - Barras de progreso
- `circles` - Círculos de progreso

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "animar": "boolean - Animar contadores",
  "items": [
    {
      "numero": "string - Ej: '10K+', '500', '99%'",
      "etiqueta": "string - Descripción",
      "icono": "string",
      "prefijo": "string - Ej: '$'",
      "sufijo": "string - Ej: '%', '+'"
    }
  ]
}
```

---

### Team
Sección de equipo/miembros.

**Tipo:** `team`

**Variantes:**
- `grid` - Cuadrícula
- `carousel` - Carrusel
- `cards` - Tarjetas
- `list` - Lista
- `hover` - Con efecto hover
- `minimal` - Minimalista

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "columnas": "2|3|4",
  "miembros": [
    {
      "nombre": "string",
      "cargo": "string",
      "foto": "url",
      "bio": "string",
      "email": "string",
      "linkedin": "url",
      "twitter": "url"
    }
  ]
}
```

---

### Gallery
Galería de imágenes.

**Tipo:** `gallery`

**Variantes:**
- `grid` - Cuadrícula
- `masonry` - Masonry
- `carousel` - Carrusel
- `lightbox` - Con lightbox
- `justified` - Justified layout
- `slider` - Slider fullwidth

**Campos de datos:**
```json
{
  "titulo": "string",
  "subtitulo": "string",
  "columnas": "2|3|4|5|6",
  "gap": "string - Ej: '16px'",
  "lightbox": "boolean",
  "imagenes": [
    {
      "url": "url - URL de la imagen",
      "alt": "string - Texto alternativo",
      "titulo": "string - Título opcional",
      "enlace": "url - Enlace al hacer clic"
    }
  ]
}
```

---

## LAYOUT

### Container
Contenedor genérico para agrupar elementos.

**Tipo:** `container`

**Campos de datos:**
```json
{
  "max_width": "full|1400px|1200px|960px|720px|540px",
  "align": "left|center|right",
  "full_height": "boolean - 100vh"
}
```

---

### Columns
Disposición en columnas con anchos personalizables.

**Tipo:** `columns`

**Campos de datos:**
```json
{
  "columnas": "2|3|4|5|6 - Número de columnas",
  "columnWidths": ["25", "50", "25"],
  "gridTemplateColumns": "25% 50% 25%",
  "gap": "string - Ej: '24px'",
  "responsive_tablet": "2|1 - Columnas en tablet",
  "responsive_mobile": "1 - Columnas en móvil"
}
```

**Presets de anchos:**
```json
{
  "equal-2": [50, 50],
  "equal-3": [33.33, 33.33, 33.34],
  "equal-4": [25, 25, 25, 25],
  "sidebar-left": [30, 70],
  "sidebar-right": [70, 30],
  "golden-left": [38.2, 61.8],
  "golden-right": [61.8, 38.2],
  "narrow-wide-narrow": [20, 60, 20],
  "wide-narrow": [66, 34]
}
```

---

### Grid
Grid CSS avanzado.

**Tipo:** `grid`

**Campos de datos:**
```json
{
  "columnas": "number - Columnas",
  "filas": "number - Filas (auto si no se especifica)",
  "gap": "string - Ej: '16px'",
  "auto_fit": "auto-fit|auto-fill - Responsivo automático",
  "min_col_width": "string - Ancho mínimo si auto_fit"
}
```

---

### Row
Fila flexbox.

**Tipo:** `row`

**Campos de datos:**
```json
{
  "justify": "flex-start|center|flex-end|space-between|space-around",
  "align": "flex-start|center|flex-end|stretch",
  "wrap": "boolean",
  "gap": "string"
}
```

---

### Spacer
Espaciador vertical.

**Tipo:** `spacer`

**Campos de datos:**
```json
{
  "altura": "string - Ej: '40px', '2rem', '10vh'"
}
```

---

### Divider
Línea divisora.

**Tipo:** `divider`

**Campos de datos:**
```json
{
  "estilo": "solid|dashed|dotted|double",
  "grosor": "string - Ej: '1px', '2px'",
  "color": "#hex",
  "ancho": "string - Ej: '100%', '50%'",
  "alineacion": "left|center|right"
}
```

---

## BÁSICOS

### Heading
Títulos y encabezados.

**Tipo:** `heading`

**Campos de datos:**
```json
{
  "texto": "string",
  "nivel": "h1|h2|h3|h4|h5|h6",
  "alineacion": "left|center|right"
}
```

---

### Paragraph
Párrafo de texto.

**Tipo:** `paragraph`

**Campos de datos:**
```json
{
  "texto": "string - Soporta HTML básico",
  "alineacion": "left|center|right|justify"
}
```

---

### Button
Botón de acción.

**Tipo:** `button`

**Variantes:**
- `solid` - Sólido
- `outline` - Con borde
- `ghost` - Transparente
- `gradient` - Con gradiente
- `rounded` - Redondeado
- `pill` - Forma de píldora
- `icon` - Con icono

**Campos de datos:**
```json
{
  "texto": "string",
  "url": "url",
  "target": "_self|_blank",
  "icono": "string",
  "icono_posicion": "left|right",
  "tamanio": "sm|md|lg|xl",
  "ancho_completo": "boolean"
}
```

---

### Image
Imagen individual.

**Tipo:** `image`

**Campos de datos:**
```json
{
  "url": "url - URL de la imagen",
  "alt": "string",
  "caption": "string",
  "enlace": "url",
  "lightbox": "boolean",
  "lazy": "boolean - Carga diferida"
}
```

---

### Icon
Icono decorativo.

**Tipo:** `icon`

**Campos de datos:**
```json
{
  "icono": "string - emoji o nombre de icono",
  "tamanio": "sm|md|lg|xl|xxl",
  "color": "#hex"
}
```

---

## WIDGETS DE MÓDULOS

Los widgets de módulos siguen el patrón:

**Tipo:** `widget_{modulo}_{widget}` o `{modulo}-{shortcode}`

### Ejemplo: Grupos de Consumo

```json
{
  "type": "gc-catalogo",
  "data": {
    "titulo": "Catálogo de Productos",
    "grupo_id": "",
    "limite": 12,
    "columnas": 3,
    "mostrar_filtros": true,
    "esquema_color": "primary",
    "radio_bordes": "lg",
    "sombra": "md"
  }
}
```

### Ejemplo: Eventos

```json
{
  "type": "eventos-proximos",
  "data": {
    "titulo": "Próximos Eventos",
    "limite": 6,
    "mostrar_calendario": true,
    "categorias": [],
    "esquema_color": "success"
  }
}
```

### Ejemplo: Socios

```json
{
  "type": "socios-listado",
  "data": {
    "titulo": "Nuestros Socios",
    "limite": 12,
    "mostrar_avatar": true,
    "columnas": 4
  }
}
```

---

## Campos de Estilo Comunes

Todos los bloques de módulos incluyen estos campos de estilo:

```json
{
  "esquema_color": "default|primary|success|warning|danger|purple|pink|dark|custom",
  "color_primario": "#hex - Solo si esquema_color=custom",
  "color_secundario": "#hex - Solo si esquema_color=custom",
  "radio_bordes": "none|sm|md|lg|xl|full",
  "sombra": "none|sm|md|lg|xl",
  "animacion_entrada": "none|fade|slide-up|slide-down|zoom|bounce"
}
```

---

## Estilos Globales

Estructura completa del objeto `styles`:

```json
{
  "spacing": {
    "margin": { "top": "", "right": "", "bottom": "", "left": "" },
    "padding": { "top": "", "right": "", "bottom": "", "left": "" }
  },
  "colors": {
    "background": "#hex o var(--flavor-bg)",
    "text": "#hex o var(--flavor-text)"
  },
  "background": {
    "type": "gradient|image|",
    "gradientDirection": "to bottom|to right|45deg|...",
    "gradientStart": "#hex",
    "gradientEnd": "#hex",
    "image": "url",
    "size": "cover|contain|auto",
    "position": "center|top|bottom|left|right",
    "repeat": "no-repeat|repeat|repeat-x|repeat-y",
    "fixed": false
  },
  "typography": {
    "fontSize": "16px|1rem|...",
    "fontWeight": "400|500|600|700|...",
    "lineHeight": "1.5|1.6|...",
    "textAlign": "left|center|right|justify"
  },
  "borders": {
    "radius": "8px|1rem|...",
    "width": "1px|2px|...",
    "color": "#hex",
    "style": "solid|dashed|dotted"
  },
  "shadows": {
    "boxShadow": "0 4px 6px rgba(0,0,0,0.1)"
  },
  "layout": {
    "display": "block|flex|grid|inline-flex",
    "flexDirection": "row|column",
    "justifyContent": "flex-start|center|flex-end|space-between",
    "alignItems": "flex-start|center|flex-end|stretch",
    "gap": "16px"
  },
  "dimensions": {
    "width": "100%|auto|500px",
    "height": "auto|100vh|500px",
    "minHeight": "300px",
    "maxWidth": "1200px"
  },
  "position": {
    "position": "relative|absolute|fixed|sticky",
    "top": "0|auto|10px",
    "right": "0|auto|10px",
    "bottom": "0|auto|10px",
    "left": "0|auto|10px",
    "zIndex": "10|100|999"
  },
  "transform": {
    "rotate": "0|-45|45|90",
    "scale": "1|1.1|0.9",
    "translateX": "0|10px|-10px",
    "translateY": "0|10px|-10px",
    "skewX": "0|5|-5",
    "skewY": "0|5|-5"
  },
  "overflow": "visible|hidden|scroll|auto",
  "opacity": "1|0.8|0.5",
  "advanced": {
    "cssId": "mi-elemento",
    "cssClasses": "clase1 clase2",
    "customCss": ".mi-elemento { ... }",
    "entranceAnimation": "fade-up|fade-down|zoom-in",
    "hoverAnimation": "scale|glow|lift"
  }
}
```

---

## Variables CSS del Tema

Usar estas variables para mantener consistencia:

```css
--flavor-primary: Color primario
--flavor-secondary: Color secundario
--flavor-accent: Color de acento
--flavor-text: Color de texto principal
--flavor-text-muted: Color de texto secundario
--flavor-bg: Color de fondo
--flavor-border: Color de bordes
```

**Uso en data:**
```json
{
  "color_fondo": "var(--flavor-primary)",
  "texto_color": "var(--flavor-text)"
}
```
