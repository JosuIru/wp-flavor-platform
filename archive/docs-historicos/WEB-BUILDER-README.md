# Sistema de Web Builder - Flavor Chat IA

Sistema de componentes flexibles tipo ACF (Advanced Custom Fields) con Tailwind CSS para crear páginas web dinámicas a partir de los módulos del plugin.

## 📋 Características

- **Componentes Flexibles**: Cada módulo puede exponer sus componentes web
- **Drag & Drop**: Interface visual para componer páginas
- **Tailwind CSS**: Diseño moderno y responsive
- **Personalizable**: Templates editables desde el tema
- **Multi-módulo**: Componentes de diferentes módulos en una misma página

## 🏗️ Arquitectura

### 1. Component Registry (`class-component-registry.php`)
Sistema central que registra y gestiona todos los componentes disponibles.

**Responsabilidades:**
- Registrar componentes de módulos
- Validar datos de componentes
- Renderizar componentes con datos
- Gestionar categorías

### 2. Page Builder (`class-page-builder.php`)
Interface de administración para construir páginas.

**Características:**
- Custom Post Type: `flavor_landing`
- Meta box con canvas de construcción
- Sidebar con librería de componentes
- Modal de edición de componentes
- Preview en tiempo real

### 3. Templates Tailwind
Ubicación: `/templates/components/[modulo]/[componente].php`

**Variables disponibles:**
```php
$component_classes // Clases CSS generadas
$component_settings // Configuración (spacing, align, etc.)
// + Todos los campos del componente
```

## 🚀 Cómo Usar

### Paso 1: Añadir Componentes a un Módulo

En tu módulo, añade el método `get_web_components()`:

```php
public function get_web_components() {
    return [
        'hero' => [
            'label' => __('Hero Section', 'flavor-chat-ia'),
            'description' => __('Sección hero con imagen de fondo', 'flavor-chat-ia'),
            'category' => 'hero', // hero, content, forms, listings, cards, etc.
            'icon' => 'dashicons-format-image',
            'fields' => [
                'titulo' => [
                    'type' => 'text',
                    'label' => __('Título', 'flavor-chat-ia'),
                    'default' => __('Bienvenido', 'flavor-chat-ia'),
                ],
                'subtitulo' => [
                    'type' => 'textarea',
                    'label' => __('Subtítulo', 'flavor-chat-ia'),
                    'default' => '',
                ],
                'imagen_fondo' => [
                    'type' => 'image',
                    'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                    'default' => '',
                ],
                'cta_texto' => [
                    'type' => 'text',
                    'label' => __('Texto del botón', 'flavor-chat-ia'),
                    'default' => __('Comenzar', 'flavor-chat-ia'),
                ],
                'cta_url' => [
                    'type' => 'url',
                    'label' => __('URL del botón', 'flavor-chat-ia'),
                    'default' => '#',
                ],
                'mostrar_estadisticas' => [
                    'type' => 'toggle',
                    'label' => __('Mostrar estadísticas', 'flavor-chat-ia'),
                    'default' => true,
                ],
            ],
            'template' => 'mi-modulo/hero', // Ruta relativa desde /templates/components/
            'preview' => '', // URL a imagen de preview (opcional)
        ],
    ];
}
```

### Paso 2: Crear el Template

Crea el archivo en `/templates/components/mi-modulo/hero.php`:

```php
<?php
/**
 * Template: Hero Mi Módulo
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var int $imagen_fondo
 * @var string $cta_texto
 * @var string $cta_url
 * @var bool $mostrar_estadisticas
 * @var string $component_classes
 */

$imagen_url = $imagen_fondo ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';
?>

<section class="relative min-h-screen flex items-center <?php echo esc_attr($component_classes); ?>">
    <?php if ($imagen_url): ?>
        <div class="absolute inset-0 z-0">
            <img src="<?php echo esc_url($imagen_url); ?>"
                 alt=""
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-blue-800/80"></div>
        </div>
    <?php endif; ?>

    <div class="relative z-10 container mx-auto px-4 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
                <?php echo esc_html($titulo); ?>
            </h1>

            <?php if ($subtitulo): ?>
                <p class="text-xl text-blue-100 mb-12">
                    <?php echo esc_html($subtitulo); ?>
                </p>
            <?php endif; ?>

            <a href="<?php echo esc_url($cta_url); ?>"
               class="inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <?php echo esc_html($cta_texto); ?>
            </a>

            <?php if ($mostrar_estadisticas): ?>
                <div class="mt-16 grid grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-white mb-2">250+</div>
                        <div class="text-blue-200 text-sm">Usuarios</div>
                    </div>
                    <!-- Más estadísticas... -->
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
```

### Paso 3: Crear una Página

1. Ve a **Landing Pages > Añadir Nueva**
2. En el meta box "Page Builder", haz clic en **Añadir Componente**
3. Selecciona tu componente de la librería
4. Edita los campos del componente
5. Añade más componentes según necesites
6. Publica la página

## 📦 Tipos de Campo Disponibles

### text
```php
'nombre_campo' => [
    'type' => 'text',
    'label' => 'Texto Simple',
    'default' => 'Valor por defecto',
]
```

### textarea
```php
'descripcion' => [
    'type' => 'textarea',
    'label' => 'Texto Largo',
    'default' => '',
]
```

### number
```php
'columnas' => [
    'type' => 'number',
    'label' => 'Número de Columnas',
    'default' => 3,
]
```

### url
```php
'enlace' => [
    'type' => 'url',
    'label' => 'URL',
    'default' => '#',
]
```

### toggle / checkbox
```php
'activo' => [
    'type' => 'toggle',
    'label' => 'Activar Feature',
    'default' => true,
]
```

### select
```php
'estilo' => [
    'type' => 'select',
    'label' => 'Estilo',
    'options' => ['clasico', 'moderno', 'minimalista'],
    'default' => 'moderno',
]
```

### image
```php
'imagen' => [
    'type' => 'image',
    'label' => 'Imagen',
    'default' => '', // Devuelve Attachment ID
]
```

### color
```php
'color_fondo' => [
    'type' => 'color',
    'label' => 'Color de Fondo',
    'default' => '#3b82f6',
]
```

## 🎨 Categorías de Componentes

- `hero` - Secciones hero principales
- `content` - Bloques de contenido
- `forms` - Formularios
- `listings` - Listados y grids
- `cards` - Tarjetas de contenido
- `navigation` - Menús y navegación
- `features` - Secciones de características
- `testimonials` - Testimonios y reseñas
- `cta` - Llamadas a la acción
- `footer` - Footers

## 🔧 Personalización de Templates

Los templates se buscan primero en el tema para permitir personalización:

1. **Tema**: `/wp-content/themes/tu-tema/flavor-components/[modulo]/[componente].php`
2. **Plugin**: `/wp-content/plugins/flavor-chat-ia/templates/components/[modulo]/[componente].php`

Para personalizar un componente, copia el template del plugin a tu tema.

## 💡 Ejemplos Completos

### Ejemplo 1: Grid de Productos

```php
'productos_grid' => [
    'label' => __('Grid de Productos', 'flavor-chat-ia'),
    'category' => 'listings',
    'icon' => 'dashicons-products',
    'fields' => [
        'titulo' => ['type' => 'text', 'label' => 'Título', 'default' => 'Nuestros Productos'],
        'columnas' => ['type' => 'select', 'label' => 'Columnas', 'options' => [2, 3, 4], 'default' => 3],
        'limite' => ['type' => 'number', 'label' => 'Cantidad', 'default' => 6],
        'categoria' => ['type' => 'text', 'label' => 'Categoría', 'default' => ''],
    ],
    'template' => 'marketplace/productos-grid',
],
```

### Ejemplo 2: Formulario de Contacto

```php
'form_contacto' => [
    'label' => __('Formulario de Contacto', 'flavor-chat-ia'),
    'category' => 'forms',
    'icon' => 'dashicons-email',
    'fields' => [
        'titulo' => ['type' => 'text', 'label' => 'Título', 'default' => '¿Hablamos?'],
        'email_destino' => ['type' => 'email', 'label' => 'Email Destino', 'default' => ''],
        'mensaje_exito' => ['type' => 'textarea', 'label' => 'Mensaje Éxito', 'default' => '¡Gracias!'],
    ],
    'template' => 'contacto/formulario',
],
```

## 🎯 Best Practices

1. **Nombres descriptivos**: Usa nombres claros para componentes y campos
2. **Defaults sensatos**: Proporciona valores por defecto útiles
3. **Validación en template**: Siempre valida y escapa datos
4. **Mobile-first**: Usa clases responsive de Tailwind
5. **Accesibilidad**: Incluye atributos ARIA y alt text
6. **Performance**: Optimiza imágenes y lazy loading

## 🐛 Debug

Para debug, activa `WP_DEBUG` y revisa:

```php
// Ver componentes registrados
$registry = Flavor_Component_Registry::get_instance();
$components = $registry->get_components();
var_dump($components);

// Ver layout de página
$layout = get_post_meta($post_id, '_flavor_page_layout', true);
var_dump($layout);
```

## 📚 Más Información

- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [WordPress Meta Boxes](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/)
- [ACF Documentation](https://www.advancedcustomfields.com/resources/)
