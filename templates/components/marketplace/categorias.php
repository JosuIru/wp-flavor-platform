<?php
/**
 * Template: Marketplace Categorias
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_categorias = $titulo_categorias ?? 'Busca por Categoria';

// Iconos SVG por defecto para categorías conocidas
$iconos_categorias = [
    'electronica' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    'hogar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    'ropa' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
    'deportes' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>',
    'libros' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'motor' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
    'mascotas' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
    'otros' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>',
];

// Colores por defecto para categorías
$colores_categorias = [
    'electronica' => '#84CC16',
    'hogar' => '#22C55E',
    'ropa' => '#10B981',
    'deportes' => '#059669',
    'libros' => '#65A30D',
    'motor' => '#16A34A',
    'mascotas' => '#4ADE80',
    'otros' => '#A3E635',
];

// Icono por defecto genérico
$icono_default = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>';

// Cargar categorías reales de la base de datos
if (!isset($categorias_marketplace)) {
    $terminos_bd = get_terms([
        'taxonomy' => 'marketplace_categoria',
        'hide_empty' => false,
    ]);

    $categorias_marketplace = [];

    if (!is_wp_error($terminos_bd) && !empty($terminos_bd)) {
        foreach ($terminos_bd as $termino) {
            $slug_normalizado = sanitize_title($termino->name);
            $categorias_marketplace[] = [
                'nombre' => $termino->name,
                'slug'   => $termino->slug,
                'icono'  => $iconos_categorias[$slug_normalizado] ?? $icono_default,
                'total'  => $termino->count,
                'color'  => $colores_categorias[$slug_normalizado] ?? '#22C55E',
            ];
        }
    }
}
?>
<section class="flavor-component flavor-section py-12 lg:py-16">
    <div class="flavor-container">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo esc_html($titulo_categorias); ?></h2>
            <p class="text-gray-500"><?php echo esc_html__('Encuentra lo que buscas rapidamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <?php if (empty($categorias_marketplace)): ?>
                <div class="col-span-full text-center py-8 text-gray-500">
                    <?php echo esc_html__('No hay categorías disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            <?php endif; ?>
            <?php foreach ($categorias_marketplace as $categoria_item) : ?>
                <a href="?categoria=<?php echo esc_attr($categoria_item['slug'] ?? sanitize_title($categoria_item['nombre'])); ?>" class="group flex flex-col items-center gap-3 p-6 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center transition-colors duration-300" style="background: <?php echo esc_attr($categoria_item['color']); ?>15;">
                        <svg class="w-7 h-7 transition-transform duration-300 group-hover:scale-110" style="color: <?php echo esc_attr($categoria_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $categoria_item['icono']; ?>
                        </svg>
                    </div>
                    <div class="text-center">
                        <h3 class="font-semibold text-gray-800 text-sm"><?php echo esc_html($categoria_item['nombre']); ?></h3>
                        <span class="text-xs text-gray-400"><?php echo esc_html($categoria_item['total']); ?> <?php echo esc_html__('anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
