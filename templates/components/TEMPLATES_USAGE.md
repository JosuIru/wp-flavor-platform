# Documentación de Templates - Flavor Chat IA

## Módulo: Bicicletas Compartidas

### 1. Hero (`bicicletas-compartidas/hero.php`)
Sección hero con buscador integrado y estadísticas en vivo.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Comparte Bicicleta', // Personalizable
    'subtitulo' => 'Transporte sostenible para tu ciudad',
    'imagen_fondo_id' => null, // ID de adjunto de WordPress
    'mostrar_buscador' => true,
    'color_primario' => '#3b82f6',
    'color_secundario' => '#06b6d4',
];
```

**Datos de ejemplo incluidos:**
- 487 bicicletas disponibles
- 52 estaciones activas
- 12,450 usuarios activos

**Elementos incluidos:**
- Formulario de búsqueda (origen, destino, tipo de viaje)
- Estadísticas en tiempo real
- Diseño responsive con gradiente

---

### 2. Mapa (`bicicletas-compartidas/mapa.php`)
Mapa interactivo de estaciones con panel lateral de información.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Estaciones de Bicicletas',
    'subtitulo' => 'Encuentra la estación más cercana',
    'estaciones' => [], // Array de estaciones
    'altura_mapa' => '500px',
    'mostrar_filtros' => true,
    'api_key_maps' => '', // Para integración con Google Maps
];
```

**Estructura de datos de estación:**
```php
[
    'id' => 1,
    'nombre' => 'Estación Central',
    'direccion' => 'Plaza Mayor, 15',
    'latitud' => 40.4158,
    'longitud' => -3.7035,
    'bicicletas_disponibles' => 12,
    'total_bicicletas' => 25,
    'abierta' => true,
]
```

**Características:**
- Filtros (todas, disponibles, cercanas)
- Indicador visual de capacidad (barra de progreso)
- Panel lateral listable y scrolleable
- Integración JavaScript para interactividad

---

### 3. Tipos (`bicicletas-compartidas/tipos.php`)
Galería de tipos de bicicletas disponibles con características detalladas.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Elige tu Bicicleta',
    'subtitulo' => 'Selecciona el modelo que mejor se adapta a tu viaje',
    'tipos' => [], // Array de tipos
];
```

**Estructura de tipo de bicicleta:**
```php
[
    'id' => 1,
    'nombre' => 'Bicicleta Urbana',
    'descripcion' => 'Perfecta para viajes cortos...',
    'icono' => '🚲',
    'color' => '#3b82f6',
    'caracteristicas' => [
        'Ruedas de 28 pulgadas',
        'Cambios: 21 velocidades',
        // ...
    ],
    'precio_por_hora' => '€0.50',
    'disponibles' => 234,
]
```

**Características:**
- Grid responsivo (1-3 columnas)
- Tarjetas con degradado de color
- Sección de características
- Información de disponibilidad
- Botones de selección

---

### 4. Cómo Usar (`bicicletas-compartidas/como-usar.php`)
Guía paso a paso con FAQ integrado.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Cómo Usar Nuestras Bicicletas',
    'subtitulo' => 'Pasos sencillos para comenzar tu viaje',
    'pasos' => [], // Array de pasos
    'mostrar_preguntas_frecuentes' => true,
];
```

**Estructura de paso:**
```php
[
    'numero' => 1,
    'titulo' => 'Descarga la App',
    'descripcion' => 'Descarga nuestra aplicación móvil...',
    'icono' => '📱',
    'color' => '#3b82f6',
]
```

**Características:**
- 6 pasos numerados con iconos
- Sección de requisitos previos
- 4 preguntas frecuentes expandibles
- Soporte multiidioma

---

## Módulo: Compostaje

### 1. Mapa (`compostaje/mapa.php`)
Mapa de puntos de compostaje con información sobre tipos aceptados.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Puntos de Compostaje',
    'subtitulo' => 'Encuentra el punto más cercano para compostar',
    'puntos_compostaje' => [], // Array de puntos
    'altura_mapa' => '500px',
    'mostrar_filtros' => true,
];
```

**Estructura de punto de compostaje:**
```php
[
    'id' => 1,
    'nombre' => 'Centro Compostaje Municipal',
    'direccion' => 'Calle Sostenibilidad, 10',
    'latitud' => 40.4158,
    'longitud' => -3.7035,
    'horario' => '8:00 - 20:00',
    'tipos_aceptados' => ['residuos_verdes', 'restos_comida', 'papel'],
    'capacidad_actual' => 75, // Porcentaje
    'contacto' => '+34 900 123 456',
    'abierta' => true,
]
```

**Tipos de residuos soportados:**
- `residuos_verdes` (verde)
- `restos_comida` (naranja)
- `papel` (azul)
- `carton` (púrpura)

**Características:**
- Filtros (todos, abiertos, cercanos)
- Indicador de capacidad visual
- Horarios disponibles
- Contacto directo
- Panel lateral interactivo

---

### 2. Guía (`compostaje/guia.php`)
Guía completa sobre qué compostar y cómo hacerlo.

**Parámetros disponibles:**
```php
$args = [
    'titulo' => 'Guía Completa de Compostaje',
    'subtitulo' => 'Aprende cómo compostar correctamente',
    'mostrar_seccion_beneficios' => true,
    'mostrar_seccion_errores' => true,
];
```

**Secciones incluidas:**
1. Qué SÍ se puede compostar (residuos verdes, restos comida, papel)
2. Qué NO se puede compostar (carnes, grasas, plásticos, etc.)
3. 4 pasos para compostar correctamente
4. Beneficios del compostaje (reduce residuos, enriquece suelo, cuida planeta)
5. Errores comunes a evitar

**Características:**
- Diseño visual con iconos
- Colores diferenciados (verde/rojo)
- Llamada a la acción (CTA)
- Información educativa completa

---

## Ejemplo de Uso en PHP

### Incluir un template:
```php
// Opción 1: Usando get_template_part (recomendado)
get_template_part(
    'templates/components/bicicletas-compartidas/hero',
    null,
    [
        'titulo' => 'Viaja en bicicleta',
        'mostrar_buscador' => true,
        'color_primario' => '#10b981',
    ]
);

// Opción 2: Incluyendo directamente
include(FLAVOR_CHAT_IA_DIR . '/templates/components/bicicletas-compartidas/hero.php');

// Opción 3: Con variables locales
$titulo = 'Mi Título';
$subtitulo = 'Mi Subtítulo';
include(FLAVOR_CHAT_IA_DIR . '/templates/components/bicicletas-compartidas/hero.php');
```

---

## Características de Seguridad

Todos los templates incluyen:
- ✓ Protección contra acceso directo: `if (!defined('ABSPATH')) exit;`
- ✓ Sanitización de salida: `esc_html()`, `esc_attr()`, `esc_url()`
- ✓ Validación de datos
- ✓ Escape de atributos en datos dinámicos
- ✓ Soporte para wp_json_encode() en JavaScript

---

## Convenciones CSS

Todos los elementos usan el prefijo `flavor-`:
- `.flavor-component` - Componente base
- `.flavor-[modulo]-[nombre]` - Especificidad del módulo
- `.flavor-[elemento]` - Elementos específicos

**Ejemplo:**
```
.flavor-bicicletas-hero
.flavor-bicicletas-mapa
.flavor-compostaje-guia
.flavor-button
.flavor-card
.flavor-filtro-btn
```

---

## Datos de Ejemplo

Todos los templates incluyen datos de ejemplo funcionales que se muestran cuando no se proporcionan parámetros. Esto permite:
- Visualización inmediata
- Testing sin datos reales
- Demostración de características

---

## Soporte Multiidioma

Todos los textos usan funciones de WordPress para traducción:
```php
__('Texto a traducir', 'flavor-platform')
_e('Texto a traducir', 'flavor-platform')
_n('singular', 'plural', $count, 'flavor-platform')
```

Dominio de texto: `flavor-platform`

---

## Responsive Design

Los templates usan Tailwind CSS con breakpoints:
- `sm:` - Pequeños (640px)
- `md:` - Medianos (768px)
- `lg:` - Grandes (1024px)

Todos los diseños son completamente responsive.

---

Creado: 2026-02-11
Versión: 1.0
