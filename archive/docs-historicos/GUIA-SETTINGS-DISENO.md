# 🎨 Guía de Settings de Diseño y Apariencia

Sistema completo de configuración visual para personalizar todos los componentes del Page Builder.

## 📍 Ubicación

**WordPress Admin** → **Landing Pages** → **Diseño**

URL: `wp-admin/edit.php?post_type=flavor_landing&page=flavor-design-settings`

---

## 🎯 Secciones de Configuración

### 1. **Colores Principales**

Paleta de colores que se aplica a todos los componentes:

| Setting | Descripción | Por Defecto | Uso |
|---------|-------------|-------------|-----|
| `primary_color` | Color primario principal | `#3b82f6` (azul) | Botones principales, enlaces, CTA |
| `secondary_color` | Color secundario | `#8b5cf6` (morado) | Botones secundarios, highlights |
| `accent_color` | Color de acento | `#f59e0b` (naranja) | Badges, iconos destacados |
| `success_color` | Color éxito | `#10b981` (verde) | Mensajes de éxito, estados positivos |
| `warning_color` | Color advertencia | `#f59e0b` (naranja) | Alertas, avisos |
| `error_color` | Color error | `#ef4444` (rojo) | Errores, validaciones fallidas |
| `background_color` | Color de fondo | `#ffffff` (blanco) | Fondo de página, cards |
| `text_color` | Color texto principal | `#1f2937` (gris oscuro) | Textos principales |
| `text_muted_color` | Color texto secundario | `#6b7280` (gris medio) | Descripciones, subtextos |

**CSS Variables generadas:**
```css
--flavor-primary: #3b82f6;
--flavor-secondary: #8b5cf6;
--flavor-accent: #f59e0b;
/* ... etc */
```

### 2. **Tipografía**

Control completo de fuentes y tamaños:

| Setting | Descripción | Por Defecto | Opciones |
|---------|-------------|-------------|----------|
| `font_family_headings` | Fuente para títulos | `Inter` | 15 Google Fonts + Sistema |
| `font_family_body` | Fuente para cuerpo | `Inter` | 15 Google Fonts + Sistema |
| `font_size_base` | Tamaño base texto | `16px` | Cualquier número |
| `font_size_h1` | Tamaño H1 | `48px` | Cualquier número |
| `font_size_h2` | Tamaño H2 | `36px` | Cualquier número |
| `font_size_h3` | Tamaño H3 | `28px` | Cualquier número |
| `line_height_base` | Interlineado base | `1.5` | Decimales |
| `line_height_headings` | Interlineado títulos | `1.2` | Decimales |

**Google Fonts disponibles:**
- Inter, Roboto, Open Sans, Lato, Montserrat
- Poppins, Raleway, Nunito, Playfair Display
- Merriweather, Source Sans Pro, Ubuntu
- Work Sans, Quicksand, DM Sans

**CSS Variables generadas:**
```css
--flavor-font-headings: "Inter", -apple-system, sans-serif;
--flavor-font-body: "Inter", -apple-system, sans-serif;
--flavor-font-size-base: 16px;
--flavor-font-size-h1: 48px;
/* ... etc */
```

### 3. **Espaciados y Layout**

Control de márgenes, padding y estructura:

| Setting | Descripción | Por Defecto | Uso |
|---------|-------------|-------------|-----|
| `container_max_width` | Ancho máximo contenedor | `1280px` | Max-width de secciones |
| `section_padding_y` | Padding vertical sección | `80px` | Espacio arriba/abajo secciones |
| `section_padding_x` | Padding horizontal sección | `20px` | Espacio lateral secciones |
| `grid_gap` | Espaciado en grids | `24px` | Gap entre elementos grid |
| `card_padding` | Padding de tarjetas | `24px` | Padding interno cards |

**CSS Variables generadas:**
```css
--flavor-container-max: 1280px;
--flavor-section-py: 80px;
--flavor-section-px: 20px;
--flavor-grid-gap: 24px;
--flavor-card-padding: 24px;
```

### 4. **Botones**

Estilo de todos los botones:

| Setting | Descripción | Por Defecto |
|---------|-------------|-------------|
| `button_border_radius` | Radio de bordes | `8px` |
| `button_padding_y` | Padding vertical | `12px` |
| `button_padding_x` | Padding horizontal | `24px` |
| `button_font_size` | Tamaño texto | `16px` |
| `button_font_weight` | Grosor texto | `600` (semi-bold) |

**CSS Variables generadas:**
```css
--flavor-button-radius: 8px;
--flavor-button-py: 12px;
--flavor-button-px: 24px;
--flavor-button-font-size: 16px;
--flavor-button-weight: 600;
```

### 5. **Componentes**

Configuración específica de componentes:

| Setting | Descripción | Por Defecto | Opciones |
|---------|-------------|-------------|----------|
| `card_border_radius` | Radio bordes tarjetas | `12px` | Cualquier número |
| `card_shadow` | Sombra de tarjetas | `medium` | none, small, medium, large, xl |
| `hero_overlay_opacity` | Opacidad overlay hero | `0.6` | 0.0 - 1.0 |
| `image_border_radius` | Radio bordes imágenes | `8px` | Cualquier número |

**Valores de sombras:**
- `none`: Sin sombra
- `small`: `0 1px 2px rgba(0,0,0,0.05)`
- `medium`: `0 4px 6px rgba(0,0,0,0.1)`
- `large`: `0 10px 15px rgba(0,0,0,0.1)`
- `xl`: `0 20px 25px rgba(0,0,0,0.1)`

**CSS Variables generadas:**
```css
--flavor-card-radius: 12px;
--flavor-card-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
--flavor-hero-overlay: 0.6;
--flavor-image-radius: 8px;
```

---

## 💻 Uso en Código

### En PHP (Templates de Componentes)

```php
<?php
// Obtener un setting específico
$primary_color = flavor_design_get('primary_color', '#3b82f6');

// Obtener todos los settings
$design_settings = flavor_design_get_all();
?>

<!-- Usar en HTML -->
<div class="hero" style="background-color: <?php echo esc_attr($primary_color); ?>;">
    <h1 style="color: <?php echo esc_attr(flavor_design_get('text_color')); ?>;">
        Título
    </h1>
</div>
```

### En CSS (Variables CSS)

```css
/* Las variables CSS están disponibles globalmente */
.mi-componente {
    background-color: var(--flavor-primary);
    color: var(--flavor-text);
    font-family: var(--flavor-font-body);
    font-size: var(--flavor-font-size-base);
}

.mi-boton {
    background: var(--flavor-secondary);
    padding: var(--flavor-button-py) var(--flavor-button-px);
    border-radius: var(--flavor-button-radius);
}

.mi-card {
    padding: var(--flavor-card-padding);
    border-radius: var(--flavor-card-radius);
    box-shadow: var(--flavor-card-shadow);
}
```

### Clases CSS Pre-definidas

El sistema genera automáticamente estas clases:

```css
/* Contenedores */
.flavor-container {
    max-width: var(--flavor-container-max);
    padding: 0 var(--flavor-section-px);
}

.flavor-section {
    padding: var(--flavor-section-py) 0;
}

/* Grids */
.flavor-grid {
    display: grid;
    gap: var(--flavor-grid-gap);
}

/* Cards */
.flavor-card {
    padding: var(--flavor-card-padding);
    border-radius: var(--flavor-card-radius);
    box-shadow: var(--flavor-card-shadow);
}

/* Botones */
.flavor-button {
    padding: var(--flavor-button-py) var(--flavor-button-px);
    border-radius: var(--flavor-button-radius);
    font-size: var(--flavor-button-font-size);
    font-weight: var(--flavor-button-weight);
}

.flavor-button-primary {
    background-color: var(--flavor-primary);
    color: white;
}

.flavor-button-secondary {
    background-color: var(--flavor-secondary);
    color: white;
}

/* Imágenes */
.flavor-image {
    border-radius: var(--flavor-image-radius);
}
```

---

## 🎬 Vista Previa en Tiempo Real

La página de settings incluye una **vista previa en vivo** que muestra:
- Títulos H1, H2, H3 con los estilos aplicados
- Textos principales y secundarios
- Botones primarios y secundarios
- Cards con sombras

Los cambios se reflejan inmediatamente en la vista previa.

---

## 🔄 Acciones Disponibles

### Restaurar Valores por Defecto
Botón: **"Restaurar Valores por Defecto"**
- Restablece todos los settings a sus valores iniciales
- No afecta a páginas ya creadas (solo nuevas)

### Exportar Configuración
Botón: **"Exportar Configuración"**
- Descarga un archivo JSON con todos los settings
- Útil para copiar diseño entre sitios

### Importar Configuración
Botón: **"Importar Configuración"**
- Carga settings desde un archivo JSON exportado
- Permite replicar diseño de otro sitio

---

## 📱 Responsive y Adaptabilidad

Los settings se aplican con **media queries automáticas**:

```css
/* Desktop */
@media (min-width: 1024px) {
    .flavor-section {
        padding: var(--flavor-section-py) var(--flavor-section-px);
    }
}

/* Tablet */
@media (max-width: 1023px) and (min-width: 640px) {
    .flavor-section {
        padding: calc(var(--flavor-section-py) * 0.75) var(--flavor-section-px);
    }
}

/* Mobile */
@media (max-width: 639px) {
    .flavor-section {
        padding: calc(var(--flavor-section-py) * 0.5) var(--flavor-section-px);
    }
}
```

---

## 🎨 Ejemplos de Uso

### Ejemplo 1: Hero Section Personalizado

```php
<?php $primary = flavor_design_get('primary_color'); ?>
<section class="flavor-section" style="background: linear-gradient(135deg, <?php echo esc_attr($primary); ?>, <?php echo esc_attr(flavor_design_get('secondary_color')); ?>);">
    <div class="flavor-container">
        <h1 style="color: white; font-size: var(--flavor-font-size-h1);">
            Bienvenido
        </h1>
        <button class="flavor-button flavor-button-primary">
            Comenzar
        </button>
    </div>
</section>
```

### Ejemplo 2: Card Grid con Diseño Personalizado

```html
<div class="flavor-section">
    <div class="flavor-container">
        <div class="flavor-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            <div class="flavor-card">
                <h3 style="color: var(--flavor-primary);">Tarjeta 1</h3>
                <p style="color: var(--flavor-text-muted);">Descripción</p>
            </div>
            <div class="flavor-card">
                <h3 style="color: var(--flavor-primary);">Tarjeta 2</h3>
                <p style="color: var(--flavor-text-muted);">Descripción</p>
            </div>
        </div>
    </div>
</div>
```

### Ejemplo 3: Botones con Diseño Consistente

```html
<div class="flavor-buttons">
    <a href="#" class="flavor-button flavor-button-primary">
        Acción Principal
    </a>
    <a href="#" class="flavor-button flavor-button-secondary">
        Acción Secundaria
    </a>
</div>
```

---

## 🔧 Personalización Avanzada

### Sobrescribir en Tema (opcional)

Puedes sobrescribir los settings en tu tema hijo:

```php
// functions.php
add_filter('flavor_design_settings', function($settings) {
    $settings['primary_color'] = '#custom-color';
    return $settings;
});
```

### Añadir Nuevas Variables CSS

```php
// functions.php
add_action('wp_head', function() {
    ?>
    <style>
    :root {
        --mi-custom-var: <?php echo flavor_design_get('primary_color'); ?>;
    }
    </style>
    <?php
}, 100);
```

---

## ✅ Checklist de Implementación

Al crear nuevos componentes, asegúrate de:

- ✅ Usar variables CSS `--flavor-*` en lugar de valores hardcoded
- ✅ Aplicar clases `.flavor-*` para estilos comunes
- ✅ Usar helpers `flavor_design_get()` en PHP
- ✅ Probar con diferentes combinaciones de colores
- ✅ Verificar responsive en diferentes tamaños
- ✅ Comprobar contraste de colores (accesibilidad)

---

## 🆘 Troubleshooting

### Los cambios no se aplican
1. Vaciar caché del navegador (Ctrl+Shift+R)
2. Vaciar caché de WordPress (si usas plugin de caché)
3. Verificar que los settings se guardaron: Ver `wp_options` → `flavor_design_settings`

### Las fuentes no cargan
1. Verificar conexión a `fonts.googleapis.com`
2. Comprobar que la fuente está en la lista de Google Fonts
3. Limpiar caché del navegador

### Las variables CSS no funcionan
1. Verificar que el CSS se está generando: Ver "Código fuente" → `<style id="flavor-design-custom-css">`
2. Comprobar compatibilidad del navegador (IE11 no soporta CSS Variables)
3. Usar fallback: `color: var(--flavor-primary, #3b82f6);`

---

**Fecha:** 2026-01-28
**Versión:** 1.0.0
**Estado:** ✅ Completamente funcional
