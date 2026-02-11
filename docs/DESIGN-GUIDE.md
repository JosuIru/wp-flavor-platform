# Guía de Diseño - Flavor Platform

## Cómo Editar la Apariencia de las Páginas Generadas

Esta guía explica las diferentes formas de personalizar el diseño visual de tu sitio Flavor Platform.

---

## Método 1: Temas Predefinidos (Más Fácil)

### Acceso
1. Ve a **WordPress Admin → Flavor Platform → Diseño**
2. En la sección "Temas" verás tarjetas con previsualizaciones

### Temas Disponibles

| Tema | Color Principal | Ideal Para |
|------|-----------------|------------|
| `default` | Azul (#3b82f6) | Uso general |
| `modern-purple` | Púrpura (#8b5cf6) | Creativos, tech |
| `ocean-blue` | Cian (#0891b2) | Salud, bienestar |
| `forest-green` | Verde (#16a34a) | Ecología, naturaleza |
| `sunset-orange` | Naranja (#ea580c) | Energía, deportes |
| `dark-mode` | Azul claro en fondo oscuro | Apps, gaming |
| `minimal` | Negro (#171717) | Diseño, arquitectura |
| `corporate` | Azul oscuro (#1e40af) | Empresas, instituciones |
| `grupos-consumo` | Verde orgánico (#4a7c59) | Alimentación, local |
| `comunidad-viva` | Índigo (#4f46e5) | Asociaciones |
| `red-cuidados` | Rosa (#ec4899) | Salud, cuidados |
| `mercado-espiral` | Verde (#2e7d32) | Comercio local |

### Aplicar un Tema
Simplemente **haz clic** en la tarjeta del tema deseado. Los cambios se aplican inmediatamente.

---

## Método 2: Personalización Manual

### Acceso
En la misma página de Diseño, debajo de los temas encontrarás secciones expandibles.

### Colores

```
┌─────────────────────────────────────────────┐
│ COLORES PRINCIPALES                          │
├─────────────────────────────────────────────┤
│ Color Primario     [#3b82f6] ← Tu marca     │
│ Color Secundario   [#8b5cf6] ← Acentos      │
│ Color de Acento    [#f59e0b] ← CTAs         │
│ Color Éxito        [#10b981] ← Confirmaciones│
│ Color Advertencia  [#f59e0b] ← Alertas      │
│ Color Error        [#ef4444] ← Errores      │
│ Color de Fondo     [#ffffff] ← Background   │
│ Color de Texto     [#1f2937] ← Texto ppal   │
│ Texto Secundario   [#6b7280] ← Subtítulos   │
└─────────────────────────────────────────────┘
```

**Consejo:** Usa herramientas como [Coolors](https://coolors.co) o [Adobe Color](https://color.adobe.com) para crear paletas armoniosas.

### Tipografía

```
┌─────────────────────────────────────────────┐
│ TIPOGRAFÍA                                   │
├─────────────────────────────────────────────┤
│ Fuente Títulos    [Inter]        ← Headings │
│ Fuente Cuerpo     [Inter]        ← Body text│
│ Tamaño Base       [16] px        ← Referencia│
│ Tamaño H1         [48] px                   │
│ Tamaño H2         [36] px                   │
│ Tamaño H3         [28] px                   │
│ Interlineado Base [1.5]          ← Legibilidad│
│ Interlineado H    [1.2]                     │
└─────────────────────────────────────────────┘
```

**Fuentes disponibles:**
- Inter (por defecto, moderna)
- Roboto (limpia, Google)
- Open Sans (legible)
- Poppins (geométrica)
- Lato (humanista)
- Montserrat (elegante)
- System (nativa del SO)

### Layout y Espaciados

```
┌─────────────────────────────────────────────┐
│ ESPACIADOS Y LAYOUT                          │
├─────────────────────────────────────────────┤
│ Ancho Contenedor   [1280] px    ← Max width │
│ Padding V Sección  [80] px      ← Arriba/abajo│
│ Padding H Sección  [20] px      ← Izq/der   │
│ Espaciado Grid     [24] px      ← Entre items│
│ Padding Tarjetas   [24] px      ← Interno   │
└─────────────────────────────────────────────┘
```

### Botones

```
┌─────────────────────────────────────────────┐
│ BOTONES                                      │
├─────────────────────────────────────────────┤
│ Radio Bordes       [8] px       ← Redondeo  │
│ Padding Vertical   [12] px                  │
│ Padding Horizontal [24] px                  │
│ Tamaño Texto       [16] px                  │
│ Grosor Texto       [600]        ← Semi-bold │
└─────────────────────────────────────────────┘
```

### Componentes

```
┌─────────────────────────────────────────────┐
│ COMPONENTES                                  │
├─────────────────────────────────────────────┤
│ Radio Tarjetas     [12] px      ← Cards     │
│ Sombra Tarjetas    [media]      ← Elevación │
│ Duración Transición [200] ms    ← Animaciones│
└─────────────────────────────────────────────┘
```

---

## Método 3: CSS Personalizado

### En el Admin
**Flavor Platform → Diseño → CSS Personalizado**

```css
/* Ejemplo: Cambiar estilo de botones primarios */
.flavor-btn--primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Ejemplo: Hero más alto */
.flavor-landing__hero {
    min-height: 90vh;
}

/* Ejemplo: Tarjetas con borde */
.flavor-card {
    border: 2px solid var(--color-primario);
}
```

### En el Tema de WordPress

Añade CSS en **Apariencia → Personalizar → CSS adicional** o en el `style.css` de tu tema hijo.

---

## Método 4: Editar Templates Directamente

Para personalizaciones avanzadas, puedes modificar los templates PHP.

### Ubicación de Templates

```
/wp-content/plugins/flavor-chat-ia/templates/
├── components/
│   ├── unified/                    # Componentes genéricos
│   │   ├── hero.php               # Dispatcher de variantes
│   │   ├── _partials/
│   │   │   ├── hero-centrado.php
│   │   │   ├── hero-split-izquierda.php
│   │   │   ├── hero-con-buscador.php
│   │   │   └── ...
│   │   ├── features.php
│   │   ├── cta.php
│   │   ├── grid.php
│   │   └── ...
│   └── landings/                   # Templates genéricos landing
│       ├── _generic-hero.php
│       ├── _generic-features.php
│       └── _generic-cta.php
└── frontend/
    └── landing/                    # Secciones específicas por módulo
        ├── _gc-ciclo-actual.php
        ├── _gc-productos-destacados.php
        └── _gc-grupos-activos.php
```

### Sobrescribir Templates (Recomendado)

Copia el template a tu tema:

```
/wp-content/themes/mi-tema/
└── flavor/
    └── components/
        └── unified/
            └── _partials/
                └── hero-centrado.php   # Tu versión personalizada
```

El plugin buscará primero en tu tema antes de usar los templates por defecto.

### Variables Disponibles en Templates

Todos los templates reciben estas variables:

```php
$color_primario    // Color principal (#3b82f6)
$titulo            // Título de la sección
$subtitulo         // Subtítulo
$cta_texto         // Texto del botón
$cta_url           // URL del botón
$imagen_fondo      // URL o ID de imagen
// ... y más según el componente
```

### Ejemplo: Personalizar Hero

```php
<?php
// /wp-content/themes/mi-tema/flavor/components/unified/_partials/hero-centrado.php

$bg_style = !empty($imagen_fondo_url)
    ? "background-image: url('{$imagen_fondo_url}');"
    : "background: linear-gradient(135deg, {$color_primario} 0%, #1a1a2e 100%);";
?>

<section class="mi-hero-custom" style="<?php echo $bg_style; ?>">
    <div class="mi-hero-content">
        <h1 class="mi-hero-titulo animate-fade-in">
            <?php echo esc_html($titulo); ?>
        </h1>
        <?php if ($subtitulo): ?>
            <p class="mi-hero-subtitulo">
                <?php echo esc_html($subtitulo); ?>
            </p>
        <?php endif; ?>
        <?php if ($texto_boton): ?>
            <a href="<?php echo esc_url($url_boton); ?>" class="mi-hero-cta">
                <?php echo esc_html($texto_boton); ?>
            </a>
        <?php endif; ?>
    </div>
</section>

<style>
.mi-hero-custom {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-size: cover;
    background-position: center;
}
.mi-hero-content {
    text-align: center;
    color: #fff;
    max-width: 800px;
    padding: 2rem;
}
.mi-hero-titulo {
    font-size: clamp(2.5rem, 5vw, 4rem);
    margin-bottom: 1rem;
}
.animate-fade-in {
    animation: fadeIn 1s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
```

---

## Variantes de Componentes

Muchos componentes soportan variantes que cambian su presentación.

### Hero

```php
[flavor_component tipo="hero" variante="centrado"]
[flavor_component tipo="hero" variante="split_izquierda"]
[flavor_component tipo="hero" variante="con_buscador"]
[flavor_component tipo="hero" variante="con_estadisticas"]
[flavor_component tipo="hero" variante="con_video"]
[flavor_component tipo="hero" variante="con_tarjetas"]
```

### Grid

```php
[flavor_component tipo="grid" variante="cards"]
[flavor_component tipo="grid" variante="lista"]
[flavor_component tipo="grid" variante="masonry"]
```

### Testimonios

```php
[flavor_component tipo="testimonios" variante="carrusel"]
[flavor_component tipo="testimonios" variante="grid"]
```

### FAQ

```php
[flavor_component tipo="faq" variante="acordeon"]
[flavor_component tipo="faq" variante="lista"]
```

---

## Variables CSS Globales

El plugin genera variables CSS que puedes usar en tu CSS personalizado:

```css
:root {
    --flavor-primary: #3b82f6;
    --flavor-secondary: #8b5cf6;
    --flavor-accent: #f59e0b;
    --flavor-success: #10b981;
    --flavor-warning: #f59e0b;
    --flavor-error: #ef4444;
    --flavor-background: #ffffff;
    --flavor-text: #1f2937;
    --flavor-text-muted: #6b7280;

    --flavor-font-headings: 'Inter', sans-serif;
    --flavor-font-body: 'Inter', sans-serif;
    --flavor-font-size-base: 16px;

    --flavor-container-max: 1280px;
    --flavor-section-padding-y: 80px;
    --flavor-grid-gap: 24px;

    --flavor-btn-radius: 8px;
    --flavor-card-radius: 12px;
    --flavor-transition: 200ms;
}
```

Uso:

```css
.mi-elemento {
    background-color: var(--flavor-primary);
    font-family: var(--flavor-font-body);
    border-radius: var(--flavor-card-radius);
    transition: all var(--flavor-transition) ease;
}
```

---

## Responsive Design

Todos los componentes son responsive por defecto. Breakpoints:

```css
/* Mobile first */
@media (min-width: 640px) { /* sm */ }
@media (min-width: 768px) { /* md */ }
@media (min-width: 1024px) { /* lg */ }
@media (min-width: 1280px) { /* xl */ }
```

Para ajustar comportamiento móvil:

```css
/* Ejemplo: Hero más pequeño en móvil */
@media (max-width: 767px) {
    .flavor-landing__hero {
        min-height: 60vh;
        padding: 2rem 1rem;
    }
    .flavor-landing__hero h1 {
        font-size: 2rem;
    }
}
```

---

## Buenas Prácticas

1. **Usa temas predefinidos** como punto de partida
2. **Ajusta colores** para tu marca usando el personalizador
3. **No edites archivos del plugin** directamente (se perderán en actualizaciones)
4. **Crea un tema hijo** para CSS y templates personalizados
5. **Usa variables CSS** del plugin para consistencia
6. **Prueba en móvil** siempre

---

## Solución de Problemas

### Los cambios no se ven
1. Limpia caché del navegador (Ctrl+Shift+R)
2. Limpia caché de WordPress si usas plugin de caché
3. Verifica que guardaste los cambios

### El tema no se aplica
1. Verifica permisos de escritura en `/wp-content/`
2. Revisa el log de errores de PHP
3. Desactiva plugins de caché temporalmente

### CSS personalizado no funciona
1. Verifica especificidad (usa `!important` si es necesario)
2. Inspecciona con DevTools para ver qué estilos se aplican
3. Verifica que el CSS esté después de los estilos del plugin

---

## Recursos

- [Coolors](https://coolors.co) - Generador de paletas
- [Google Fonts](https://fonts.google.com) - Tipografías
- [Heroicons](https://heroicons.com) - Iconos SVG
- [Tailwind Colors](https://tailwindcss.com/docs/customizing-colors) - Referencia de colores
