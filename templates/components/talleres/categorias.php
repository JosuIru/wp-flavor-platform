<?php
/**
 * Template: Categorias Talleres
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_categorias = $titulo_categorias ?? 'Explora por Categoria';
$subtitulo_categorias = $subtitulo_categorias ?? 'Encuentra el taller perfecto para ti segun tus intereses';

$categorias_talleres = $categorias_talleres ?? [
    [
        'nombre'      => 'Cocina',
        'slug'        => 'cocina',
        'descripcion' => 'Recetas, reposteria y cocina saludable',
        'cantidad'    => 24,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
        'color_desde' => '#F97316',
        'color_hasta' => '#DC2626',
    ],
    [
        'nombre'      => 'Arte',
        'slug'        => 'arte',
        'descripcion' => 'Pintura, escultura y expresion creativa',
        'cantidad'    => 18,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>',
        'color_desde' => '#EC4899',
        'color_hasta' => '#BE185D',
    ],
    [
        'nombre'      => 'Tecnologia',
        'slug'        => 'tecnologia',
        'descripcion' => 'Informatica, programacion y habilidades digitales',
        'cantidad'    => 15,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'color_desde' => '#3B82F6',
        'color_hasta' => '#0891B2',
    ],
    [
        'nombre'      => 'Bienestar',
        'slug'        => 'bienestar',
        'descripcion' => 'Yoga, meditacion y vida saludable',
        'cantidad'    => 21,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
        'color_desde' => '#10B981',
        'color_hasta' => '#059669',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-50">
    <div class="flavor-container">
        <!-- Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-3">
                <?php echo esc_html($titulo_categorias); ?>
            </h2>
            <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_categorias); ?>
            </p>
        </div>

        <!-- Grid de Categorias -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">
            <?php foreach ($categorias_talleres as $categoria_item) : ?>
                <a href="<?php echo esc_url('/talleres/?categoria=' . $categoria_item['slug']); ?>"
                   class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 text-center block">
                    <!-- Icono -->
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-transform duration-300 group-hover:scale-110"
                         style="background: linear-gradient(135deg, <?php echo esc_attr($categoria_item['color_desde']); ?>, <?php echo esc_attr($categoria_item['color_hasta']); ?>);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $categoria_item['icono']; ?>
                        </svg>
                    </div>

                    <!-- Nombre -->
                    <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-fuchsia-600 transition-colors">
                        <?php echo esc_html($categoria_item['nombre']); ?>
                    </h3>

                    <!-- Descripcion -->
                    <p class="text-sm text-gray-500 mb-3 leading-relaxed">
                        <?php echo esc_html($categoria_item['descripcion']); ?>
                    </p>

                    <!-- Contador -->
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                          style="background: <?php echo esc_attr($categoria_item['color_desde']); ?>15; color: <?php echo esc_attr($categoria_item['color_desde']); ?>;">
                        <?php echo esc_html($categoria_item['cantidad']); ?> <?php echo esc_html__('talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>

                    <!-- Flecha -->
                    <div class="mt-4 flex items-center justify-center gap-1 text-sm font-medium text-gray-400 group-hover:text-fuchsia-600 transition-colors">
                        <?php echo esc_html__('Ver talleres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
