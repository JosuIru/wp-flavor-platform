# Módulo: Sector Empresarial

> Componentes profesionales para empresas: héroes corporativos, servicios, equipo, testimonios, estadísticas

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `empresarial` |
| **Versión** | 1.0.0+ |
| **Categoría** | Web / Diseño |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

Módulo que proporciona componentes web profesionales para sitios empresariales. Incluye bloques para Visual Builder Pro con diseños corporativos predefinidos.

### Características Principales

- **Hero Corporativo**: Cabeceras profesionales con video
- **Grid de Servicios**: Mostrar servicios en grid
- **Equipo**: Presentación de miembros del equipo
- **Testimonios**: Carrusel de testimonios de clientes
- **Estadísticas**: Métricas y logros
- **Contacto**: Formulario profesional
- **Pricing**: Tabla de precios
- **Portfolio**: Casos de éxito

---

## Componentes Web (Visual Builder Pro)

### empresarial_hero

Hero profesional para empresas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo` | text | Título principal |
| `subtitulo` | textarea | Subtítulo |
| `texto_boton_principal` | text | Botón CTA principal |
| `url_boton_principal` | url | URL del botón |
| `texto_boton_secundario` | text | Botón secundario |
| `url_boton_secundario` | url | URL secundario |
| `imagen_fondo` | image | Imagen de fondo |
| `mostrar_video` | toggle | Mostrar video |
| `url_video` | url | URL de video |

### empresarial_servicios

Grid de servicios o soluciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título de sección |
| `descripcion_seccion` | textarea | Descripción |
| `columnas` | select | 2, 3 o 4 columnas |
| `estilo` | select | cards, minimal, bordered |
| `items` | repeater | Lista de servicios |

### empresarial_equipo

Presentación del equipo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `layout` | select | grid, slider, list |
| `columnas` | select | 2, 3 o 4 columnas |
| `mostrar_redes_sociales` | toggle | Mostrar RRSS |
| `items` | repeater | Miembros del equipo |

Campos del repeater:
- `nombre`, `puesto`, `bio`, `foto`, `linkedin`, `twitter`, `email`

### empresarial_testimonios

Testimonios de clientes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `layout` | select | carousel, grid, masonry |
| `mostrar_foto` | toggle | Mostrar foto |
| `mostrar_empresa` | toggle | Mostrar empresa |
| `items` | repeater | Testimonios |

### empresarial_stats

Estadísticas y métricas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `estilo` | select | minimal, cards, highlighted |
| `items` | repeater | Estadísticas (numero + texto) |

### empresarial_contacto

Formulario de contacto profesional.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `descripcion_seccion` | textarea | Descripción |
| `layout` | select | simple, con_mapa, dos_columnas |
| `email_destino` | text | Email para recibir |
| `telefono` | text | Teléfono de contacto |
| `direccion` | textarea | Dirección física |

### empresarial_pricing

Tabla de precios y planes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `periodo` | select | mensual, anual, ambos |
| `items` | repeater | Planes |

Campos del repeater:
- `nombre`, `descripcion`, `precio_mensual`, `precio_anual`, `caracteristicas`, `destacar`, `badge`

### empresarial_portfolio

Portfolio y casos de éxito.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `titulo_seccion` | text | Título |
| `layout` | select | grid, masonry, carousel |
| `columnas` | select | 2, 3 o 4 |
| `mostrar_filtros` | toggle | Filtros por categoría |
| `items` | repeater | Proyectos |

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[empresarial_servicios]` | Grid de servicios |
| `[empresarial_equipo]` | Equipo/Staff |
| `[empresarial_testimonios]` | Testimonios |
| `[empresarial_contacto]` | Formulario contacto |
| `[empresarial_portfolio]` | Portfolio |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `empresarial_contacto_form` | Procesar formulario | Público |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `empresarial-dashboard` | Panel principal |

---

## Notas de Implementación

- Los componentes se registran para Visual Builder Pro
- Cada componente tiene su template en `templates/empresarial/`
- Estilos responsivos incluidos
- Soporte para fuentes de datos dinámicas (posts)
- Los repeaters permiten máximo 12 items
- Integración con formularios AJAX
