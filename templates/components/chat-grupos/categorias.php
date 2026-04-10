<?php
/**
 * Template: Chat Grupos Categorias
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_categorias = $titulo_categorias ?? 'Explora por Categoria';

$categorias_grupos = $categorias_grupos ?? [
    [
        'nombre' => 'Deportes',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>',
        'total'  => 12,
        'color'  => '#EC4899',
    ],
    [
        'nombre' => 'Cultura',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>',
        'total'  => 8,
        'color'  => '#D946EF',
    ],
    [
        'nombre' => 'Tecnologia',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'total'  => 15,
        'color'  => '#A855F7',
    ],
    [
        'nombre' => 'Gastronomia',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'total'  => 10,
        'color'  => '#F43F5E',
    ],
    [
        'nombre' => 'Idiomas',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>',
        'total'  => 6,
        'color'  => '#8B5CF6',
    ],
    [
        'nombre' => 'Naturaleza',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
        'total'  => 7,
        'color'  => '#10B981',
    ],
    [
        'nombre' => 'Musica',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>',
        'total'  => 9,
        'color'  => '#F59E0B',
    ],
    [
        'nombre' => 'Viajes',
        'icono'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'total'  => 5,
        'color'  => '#06B6D4',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16">
    <div class="flavor-container">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo esc_html($titulo_categorias); ?></h2>
            <p class="text-gray-500"><?php echo esc_html__('Encuentra grupos segun tus intereses', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <?php foreach ($categorias_grupos as $categoria_item) : ?>
                <a href="?categoria=<?php echo esc_attr(sanitize_title($categoria_item['nombre'])); ?>" class="group flex flex-col items-center gap-3 p-6 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center transition-colors duration-300" style="background: <?php echo esc_attr($categoria_item['color']); ?>15;">
                        <svg class="w-7 h-7 transition-transform duration-300 group-hover:scale-110" style="color: <?php echo esc_attr($categoria_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $categoria_item['icono']; ?>
                        </svg>
                    </div>
                    <div class="text-center">
                        <h3 class="font-semibold text-gray-800 text-sm"><?php echo esc_html($categoria_item['nombre']); ?></h3>
                        <span class="text-xs text-gray-400"><?php echo esc_html($categoria_item['total']); ?> <?php echo esc_html__('grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
