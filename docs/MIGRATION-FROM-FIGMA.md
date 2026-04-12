# Workflow Figma a VBP

Guia para implementar disenos de Figma en Visual Builder Pro.

> **Nota:** Esta no es una "migracion" en el sentido tradicional, ya que Figma es una herramienta de diseno y VBP es un builder. Esta guia cubre el flujo de trabajo para convertir disenos de Figma en sitios web funcionales con VBP.

---

## Indice

1. [Figma y VBP: Roles Complementarios](#figma-y-vbp-roles-complementarios)
2. [Preparacion en Figma](#preparacion-en-figma)
3. [Exportar Assets](#exportar-assets)
4. [Mapeo de Componentes](#mapeo-de-componentes)
5. [Implementacion en VBP](#implementacion-en-vbp)
6. [Design Tokens](#design-tokens)
7. [Auto Layout a CSS](#auto-layout-a-css)
8. [Variantes y Estados](#variantes-y-estados)
9. [Prototipo a Interacciones](#prototipo-a-interacciones)
10. [Flujo de Trabajo Optimo](#flujo-de-trabajo-optimo)

---

## Figma y VBP: Roles Complementarios

### Cuando usar cada uno

| Escenario | Herramienta |
|-----------|-------------|
| Explorar ideas iniciales | Figma |
| Crear wireframes | Figma o VBP |
| Disenar UI detallada | Figma o VBP |
| Construir sitio final | VBP |
| Iterar rapidamente | VBP |
| Colaborar con stakeholders | VBP (tiene preview real) |
| Design system visual | Figma |
| Design system funcional | VBP |

### VBP puede reemplazar Figma?

En muchos casos, si. VBP tiene canvas infinito, smart guides, componentes, variantes - todo lo que necesitas para disenar. La ventaja es que lo que disenas ES el sitio final.

```
FLUJO TRADICIONAL:
Figma Design -> Handoff -> Developer -> WordPress
     |                         |
     +---- Inconsistencias ----+
     +---- Tiempo duplicado ---+

FLUJO CON VBP:
VBP Design = WordPress Final
     |
     +---- 1 sola fuente de verdad
     +---- Sin handoff
```

### Cuando seguir usando Figma

- Proyectos con equipos de diseno grandes ya usando Figma
- Clientes que quieren aprobar en Figma antes de implementar
- Design systems compartidos entre web/mobile/desktop
- Prototipado de alta fidelidad para user testing

---

## Preparacion en Figma

### 1. Organizar el archivo Figma

```
ESTRUCTURA RECOMENDADA:
├── Cover (portada del proyecto)
├── Design System
│   ├── Colors
│   ├── Typography
│   ├── Spacing
│   └── Effects
├── Components
│   ├── Buttons
│   ├── Cards
│   ├── Navigation
│   └── Forms
├── Pages
│   ├── Home
│   ├── About
│   ├── Services
│   └── Contact
└── Prototypes
```

### 2. Usar Auto Layout correctamente

Auto Layout en Figma se traduce directamente a Flexbox en VBP:

| Figma Auto Layout | VBP/CSS |
|-------------------|---------|
| Direction: Horizontal | `flex-direction: row` |
| Direction: Vertical | `flex-direction: column` |
| Gap | `gap` |
| Padding | `padding` |
| Alignment | `align-items`, `justify-content` |
| Fill container | `width: 100%` |
| Hug contents | `width: auto` |
| Fixed | `width: Xpx` |

### 3. Nombrar capas semanticamente

```
BUENO:
- hero-section
- nav-primary
- card-product
- btn-primary

MALO:
- Frame 42
- Rectangle 15
- Group 7
```

### 4. Usar componentes consistentemente

Cada componente de Figma debe tener su equivalente VBP planificado.

---

## Exportar Assets

### 1. Imagenes y graficos

```bash
# En Figma:
# 1. Seleccionar elemento
# 2. Export panel (derecha)
# 3. Formato: PNG @2x para retina, SVG para iconos/logos

# Organizacion recomendada:
wp-content/uploads/design/
├── icons/
│   ├── icon-check.svg
│   ├── icon-arrow.svg
│   └── ...
├── images/
│   ├── hero-bg.jpg
│   ├── hero-bg@2x.jpg
│   └── ...
└── logos/
    ├── logo.svg
    └── logo-white.svg
```

### 2. Iconos como SVG

```bash
# Exportar iconos individuales como SVG
# Optimizar con SVGO:
npx svgo icon.svg -o icon.min.svg

# O usar directamente en VBP:
{
  "type": "icon",
  "props": {
    "name": "check",
    "size": 24,
    "color": "currentColor"
  }
}
```

### 3. Extraer CSS con plugins

Figma plugins utiles:
- **CSS Generator** - Extrae CSS de elementos
- **Design Tokens** - Exporta tokens como JSON
- **Figma to Code** - Genera HTML/CSS

---

## Mapeo de Componentes

### Figma Components -> VBP Blocks

| Figma Component | VBP Block |
|-----------------|-----------|
| Text (heading) | `heading` |
| Text (body) | `text` |
| Button | `button` |
| Input field | `form` field |
| Image | `image` |
| Icon | `icon` |
| Card | `card` o custom |
| Navigation | `nav-menu` |
| Hero section | `section` con children |
| Feature list | `columns` + `feature-card` |
| Testimonial | `testimonial` |
| Footer | `footer` template |

### Componentes con variantes

**Figma:**
```
Button
├── Variant: Primary
├── Variant: Secondary
├── Variant: Ghost
├── State: Default
├── State: Hover
└── State: Disabled
```

**VBP:**
```json
{
  "type": "button",
  "props": {
    "text": "Click me",
    "variant": "primary",
    "size": "medium"
  }
}
```

VBP maneja variantes via props, no necesitas crear multiples componentes.

---

## Implementacion en VBP

### 1. Crear estructura de secciones

Analizar el diseno de Figma y dividir en secciones:

```
HOME PAGE:
1. Hero section
2. Features section
3. How it works section
4. Testimonials section
5. CTA section
6. Footer
```

### 2. Implementar seccion por seccion

```bash
# Ejemplo: Hero section de Figma a VBP

curl -X POST "http://sitio.local/wp-json/flavor-vbp/v1/claude/pages/styled" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Home",
    "content": {
      "blocks": [
        {
          "type": "section",
          "props": {
            "className": "hero-section",
            "background": {
              "type": "image",
              "src": "/uploads/design/images/hero-bg.jpg",
              "overlay": "rgba(0,0,0,0.5)"
            },
            "padding": "120px 0"
          },
          "children": [
            {
              "type": "container",
              "props": {"maxWidth": "800px", "textAlign": "center"},
              "children": [
                {
                  "type": "heading",
                  "props": {
                    "level": 1,
                    "text": "Build Beautiful Websites",
                    "color": "#ffffff",
                    "fontSize": "56px",
                    "fontWeight": "700"
                  }
                },
                {
                  "type": "text",
                  "props": {
                    "content": "Create stunning, high-performance websites without code.",
                    "color": "#ffffff",
                    "fontSize": "20px",
                    "marginTop": "24px"
                  }
                },
                {
                  "type": "container",
                  "props": {"display": "flex", "gap": "16px", "justifyContent": "center", "marginTop": "40px"},
                  "children": [
                    {"type": "button", "props": {"text": "Get Started", "style": "primary", "size": "large"}},
                    {"type": "button", "props": {"text": "Learn More", "style": "outline", "size": "large"}}
                  ]
                }
              ]
            }
          ]
        }
      ]
    }
  }'
```

### 3. Usar el inspector visual

En lugar de escribir JSON, usa el editor VBP:

1. Abrir pagina en VBP editor
2. Arrastrar bloques desde panel izquierdo
3. Ajustar propiedades en inspector derecho
4. Ver cambios en tiempo real en canvas

---

## Design Tokens

### 1. Extraer tokens de Figma

Usar plugin "Design Tokens" o extraer manualmente:

```json
// figma-tokens.json
{
  "colors": {
    "primary": {
      "50": "#eff6ff",
      "100": "#dbeafe",
      "500": "#3b82f6",
      "600": "#2563eb",
      "700": "#1d4ed8"
    },
    "gray": {
      "50": "#f9fafb",
      "100": "#f3f4f6",
      "500": "#6b7280",
      "900": "#111827"
    }
  },
  "typography": {
    "fontFamily": {
      "sans": "Inter, sans-serif",
      "heading": "Poppins, sans-serif"
    },
    "fontSize": {
      "xs": "12px",
      "sm": "14px",
      "base": "16px",
      "lg": "18px",
      "xl": "20px",
      "2xl": "24px",
      "3xl": "30px",
      "4xl": "36px",
      "5xl": "48px"
    },
    "fontWeight": {
      "normal": "400",
      "medium": "500",
      "semibold": "600",
      "bold": "700"
    },
    "lineHeight": {
      "tight": "1.25",
      "normal": "1.5",
      "relaxed": "1.75"
    }
  },
  "spacing": {
    "0": "0",
    "1": "4px",
    "2": "8px",
    "3": "12px",
    "4": "16px",
    "5": "20px",
    "6": "24px",
    "8": "32px",
    "10": "40px",
    "12": "48px",
    "16": "64px",
    "20": "80px"
  },
  "borderRadius": {
    "none": "0",
    "sm": "4px",
    "md": "8px",
    "lg": "12px",
    "xl": "16px",
    "full": "9999px"
  },
  "shadow": {
    "sm": "0 1px 2px 0 rgba(0, 0, 0, 0.05)",
    "md": "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
    "lg": "0 10px 15px -3px rgba(0, 0, 0, 0.1)",
    "xl": "0 20px 25px -5px rgba(0, 0, 0, 0.1)"
  }
}
```

### 2. Importar a VBP

```bash
curl -X POST "http://sitio.local/wp-json/flavor-vbp/v1/claude/design-tokens" \
  -H "X-VBP-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d @figma-tokens.json
```

### 3. Usar tokens en bloques

```json
{
  "type": "button",
  "props": {
    "text": "Click me",
    "backgroundColor": "var(--color-primary-600)",
    "color": "var(--color-white)",
    "padding": "var(--spacing-3) var(--spacing-6)",
    "borderRadius": "var(--radius-md)",
    "fontSize": "var(--font-size-base)",
    "fontWeight": "var(--font-weight-semibold)"
  }
}
```

---

## Auto Layout a CSS

### Traduccion directa

| Figma | CSS/VBP |
|-------|---------|
| Auto Layout horizontal | `display: flex; flex-direction: row;` |
| Auto Layout vertical | `display: flex; flex-direction: column;` |
| Gap: 16 | `gap: 16px;` |
| Padding: 24 | `padding: 24px;` |
| Align: Top Left | `align-items: flex-start; justify-content: flex-start;` |
| Align: Center | `align-items: center; justify-content: center;` |
| Space between | `justify-content: space-between;` |
| Fill container | `flex: 1;` o `width: 100%;` |
| Hug contents | `width: auto;` |
| Fixed width | `width: Xpx;` |

### Ejemplo completo

**Figma Auto Layout:**
```
Frame: Card
├── Auto Layout: Vertical
├── Gap: 16
├── Padding: 24
├── Align: Left
└── Children:
    ├── Image (Fill width)
    ├── Title (Hug)
    └── Description (Hug)
```

**VBP:**
```json
{
  "type": "container",
  "props": {
    "display": "flex",
    "flexDirection": "column",
    "gap": "16px",
    "padding": "24px",
    "alignItems": "flex-start"
  },
  "children": [
    {
      "type": "image",
      "props": {"src": "/image.jpg", "width": "100%"}
    },
    {
      "type": "heading",
      "props": {"level": 3, "text": "Title"}
    },
    {
      "type": "text",
      "props": {"content": "Description text here."}
    }
  ]
}
```

---

## Variantes y Estados

### Variantes de componentes

En Figma defines variantes explicitamente. En VBP, usas props:

**Figma:**
```
Button Component
├── Size: Small, Medium, Large
├── Style: Primary, Secondary, Ghost
└── State: Default, Hover, Disabled
```

**VBP:**
```json
// El mismo bloque, diferentes props
{"type": "button", "props": {"size": "small", "variant": "primary"}}
{"type": "button", "props": {"size": "medium", "variant": "secondary"}}
{"type": "button", "props": {"size": "large", "variant": "ghost"}}
```

### Estados interactivos

VBP maneja estados con CSS/animations:

```json
{
  "type": "button",
  "props": {
    "text": "Hover me",
    "hoverStyles": {
      "backgroundColor": "var(--color-primary-700)",
      "transform": "translateY(-2px)",
      "boxShadow": "var(--shadow-lg)"
    },
    "transition": "all 0.2s ease"
  }
}
```

---

## Prototipo a Interacciones

### Mapeo de interacciones

| Figma Prototype | VBP Animation |
|-----------------|---------------|
| On Click -> Navigate | Link |
| On Click -> Open Overlay | Modal |
| On Hover -> Change variant | Hover animation |
| While Scrolling | Scroll animation |
| After Delay | Animation delay |
| Smart Animate | Auto-animate |

### Ejemplo: Modal

**Figma:** On Click -> Open Overlay (Modal)

**VBP:**
```json
{
  "type": "button",
  "props": {
    "text": "Open Modal",
    "onClick": {
      "action": "openModal",
      "target": "contact-modal"
    }
  }
}
```

```json
{
  "type": "modal",
  "props": {
    "id": "contact-modal",
    "animation": "fade-in",
    "closeOnOverlay": true
  },
  "children": [
    {"type": "heading", "props": {"text": "Contact Us"}},
    {"type": "form", "props": {...}}
  ]
}
```

### Ejemplo: Scroll Animation

**Figma:** While Scrolling (parallax effect)

**VBP:**
```json
{
  "type": "section",
  "props": {
    "scrollAnimation": {
      "type": "parallax",
      "speed": 0.5,
      "elements": [
        {"selector": ".hero-image", "speed": 0.3},
        {"selector": ".hero-text", "speed": 0.1}
      ]
    }
  }
}
```

---

## Flujo de Trabajo Optimo

### Opcion A: Figma -> VBP (tradicional)

Para equipos con disenadores dedicados en Figma:

```
1. DISENAR EN FIGMA
   ├── Crear design system
   ├── Disenar todas las paginas
   └── Obtener aprobacion

2. EXPORTAR
   ├── Tokens -> JSON
   ├── Assets -> PNG/SVG
   └── Specs -> Documentar

3. IMPLEMENTAR EN VBP
   ├── Importar tokens
   ├── Crear bloques
   └── Construir paginas

4. ITERAR
   ├── Cambios en VBP directamente
   └── Sincronizar cambios grandes con Figma
```

### Opcion B: VBP First (recomendado)

Para equipos agiles o proyectos nuevos:

```
1. DISENAR DIRECTAMENTE EN VBP
   ├── Canvas infinito
   ├── Componentes reutilizables
   └── Vista previa real

2. BENEFICIOS
   ├── Sin handoff
   ├── Sin inconsistencias
   ├── Cambios instantaneos
   └── Colaboracion real-time

3. FIGMA SOLO PARA
   ├── Explorar conceptos iniciales
   ├── Compartir con stakeholders externos
   └── Design system cross-platform
```

### Opcion C: Hibrido

```
1. EXPLORAR EN FIGMA
   └── Ideas iniciales, wireframes

2. DISENAR EN VBP
   └── Diseno detallado directamente

3. DOCUMENTAR EN FIGMA (opcional)
   └── Para design system compartido
```

### Herramientas de sincronizacion

Si necesitas mantener Figma y VBP sincronizados:

```bash
# Exportar estructura VBP a Figma (via API)
curl "http://sitio.local/wp-json/flavor-vbp/v1/claude/export/figma" \
  -H "X-VBP-Key: $API_KEY" > vbp-structure.json

# Importar a Figma con plugin custom
# O usar como referencia para actualizar Figma manualmente
```

---

## Conclusiones

### VBP como alternativa a Figma

Para muchos proyectos web, VBP puede reemplazar completamente a Figma:

| Necesidad | Figma | VBP |
|-----------|-------|-----|
| Canvas libre | ✅ | ✅ |
| Smart guides | ✅ | ✅ |
| Componentes | ✅ | ✅ |
| Variantes | ✅ | ✅ |
| Auto Layout | ✅ | ✅ (CSS nativo) |
| Colaboracion | ✅ | ✅ |
| Prototipado | ✅ | ✅ |
| Output final | ❌ | ✅ |
| CMS integrado | ❌ | ✅ |
| Publicacion | ❌ | ✅ |

### Cuando mantener Figma

- Equipos muy grandes ya establecidos en Figma
- Design systems para multiples plataformas
- Stakeholders que solo quieren ver mocks
- Proyectos donde el 80% es explorar, 20% implementar

### Proximos pasos

1. **Probar VBP** para tu proximo proyecto
2. **Comparar** tiempo de Figma+implementacion vs VBP solo
3. **Evaluar** si tu equipo puede transicionar
4. **Decidir** el flujo optimo para tu contexto

---

## Recursos

- [Documentacion VBP](./vbp/README.md)
- [Bloques disponibles](./vbp/api/blocks.md)
- [Design tokens](./vbp/features/design-tokens.md)
- [Animaciones](./vbp/features/animation-builder.md)

---

*Guia actualizada: Abril 2026*
*Version: 1.0*
