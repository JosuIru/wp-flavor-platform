# Templates del Módulo Cursos

Este directorio contiene los templates para el módulo de cursos del plugin FlavorChatIA.

## Templates Disponibles

### 1. hero.php
Hero section destacada para la página principal de cursos.

**Parámetros disponibles:**
- `titulo_principal` - Título principal del hero
- `descripcion_hero` - Descripción del hero
- `boton_principal_texto` - Texto del botón principal
- `boton_principal_url` - URL del botón principal
- `boton_secundario_texto` - Texto del botón secundario
- `boton_secundario_url` - URL del botón secundario
- `imagen_fondo` - URL de imagen de fondo opcional
- `mostrar_estadisticas` - Mostrar/ocultar estadísticas (boolean)
- `total_cursos` - Número total de cursos
- `total_estudiantes` - Número total de estudiantes
- `total_instructores` - Número total de instructores

**Ejemplo de uso:**
```php
get_template_part('includes/templates/components/cursos/hero', null, [
    'titulo_principal' => 'Aprende y Crece',
    'total_cursos' => 150,
    'total_estudiantes' => 5000
]);
```

### 2. cursos-grid.php
Grid de cursos con búsqueda, filtros y paginación.

**Parámetros disponibles:**
- `titulo_seccion` - Título de la sección
- `subtitulo_seccion` - Subtítulo de la sección
- `cursos` - Array de cursos (ver estructura abajo)
- `mostrar_filtros` - Mostrar/ocultar filtros (boolean)
- `mostrar_buscador` - Mostrar/ocultar buscador (boolean)
- `columnas` - Clases de Tailwind para el grid
- `categorias` - Array de categorías para el filtro
- `mensaje_sin_cursos` - Mensaje cuando no hay cursos

**Estructura de un curso:**
```php
[
    'id' => 1,
    'titulo' => 'Título del Curso',
    'descripcion' => 'Descripción breve',
    'imagen' => 'url-imagen.jpg',
    'instructor' => 'Nombre Instructor',
    'duracion' => '10 horas',
    'nivel' => 'Intermedio',
    'precio' => 49.99,
    'precio_descuento' => 39.99,
    'estudiantes' => 1500,
    'rating' => 4.5,
    'total_reviews' => 280,
    'url' => 'url-del-curso',
    'categoria' => 'categoria-id',
    'etiquetas' => ['tag1', 'tag2']
]
```

### 3. categorias-cursos.php
Navegación visual por categorías de cursos.

**Parámetros disponibles:**
- `titulo_seccion` - Título de la sección
- `subtitulo_seccion` - Subtítulo de la sección
- `categorias` - Array de categorías (ver estructura abajo)
- `mostrar_contador` - Mostrar contador de cursos (boolean)
- `layout` - Tipo de layout: 'grid' o 'featured'
- `columnas` - Clases de Tailwind para el grid

**Estructura de una categoría:**
```php
[
    'id' => 1,
    'nombre' => 'Programación',
    'descripcion' => 'Aprende a programar',
    'icono' => 'code', // book, code, design, business, science, art, music, language, health, marketing
    'color' => 'blue', // blue, green, purple, red, yellow, pink, indigo, teal
    'total_cursos' => 45,
    'url' => 'url-categoria',
    'imagen' => 'url-imagen-opcional.jpg'
]
```

### 4. cta-instructor.php
Call-to-action para invitar a usuarios a ser instructores.

**Parámetros disponibles:**
- `titulo` - Título del CTA
- `subtitulo` - Subtítulo del CTA
- `descripcion` - Descripción extendida
- `boton_texto` - Texto del botón principal
- `boton_url` - URL del botón principal
- `boton_secundario_texto` - Texto del botón secundario
- `boton_secundario_url` - URL del botón secundario
- `mostrar_beneficios` - Mostrar/ocultar beneficios (boolean)
- `mostrar_estadisticas` - Mostrar/ocultar estadísticas (boolean)
- `imagen` - URL de imagen de fondo
- `variante` - Estilo: 'default', 'minimal' o 'featured'
- `beneficios` - Array de beneficios
- `estadisticas` - Array de estadísticas

**Estructura de un beneficio:**
```php
[
    'icono' => 'money', // money, users, tools, support, certificate
    'titulo' => 'Genera Ingresos',
    'descripcion' => 'Descripción del beneficio'
]
```

**Estructura de una estadística:**
```php
[
    'valor' => '10,000+',
    'etiqueta' => 'Estudiantes Activos'
]
```

## Características Generales

- **Tailwind CSS**: Todos los templates usan Tailwind CSS para estilos
- **Responsive**: Diseño adaptable a móviles, tablets y desktop
- **Accesibilidad**: Incluye atributos ARIA y estructura semántica
- **Iconos**: Usa SVG inline para mejor rendimiento
- **Texto en Español**: Todo el contenido está en español
- **Production-Ready**: Código limpio y optimizado para producción

## Ejemplo de Página Completa

```php
<?php
// Página de cursos completa

// Hero
get_template_part('includes/templates/components/cursos/hero', null, [
    'titulo_principal' => 'Aprende y Crece con Nuestros Cursos',
    'total_cursos' => 150,
    'total_estudiantes' => 5000,
    'total_instructores' => 50
]);

// Categorías
get_template_part('includes/templates/components/cursos/categorias-cursos', null, [
    'categorias' => $mis_categorias
]);

// Grid de cursos
get_template_part('includes/templates/components/cursos/cursos-grid', null, [
    'cursos' => $mis_cursos,
    'categorias' => $categorias_filtro
]);

// CTA Instructor
get_template_part('includes/templates/components/cursos/cta-instructor', null, [
    'variante' => 'featured'
]);
?>
```

## Notas de Desarrollo

- Los templates usan `defined('ABSPATH') || exit;` para seguridad
- Todos los outputs usan escapado apropiado (`esc_html`, `esc_url`, `esc_attr`)
- Los arrays de datos se validan con valores por defecto usando el operador `??`
- JavaScript inline incluido solo cuando es necesario para funcionalidad básica
- Variables con nombres descriptivos según las instrucciones del usuario
