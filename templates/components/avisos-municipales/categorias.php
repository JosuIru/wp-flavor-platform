<?php
/**
 * Template: Categorias de Avisos - Avisos Municipales
 *
 * Grid de categorias de avisos municipales con iconos representativos.
 * Categorias: obras, trafico, medio ambiente, cultura, servicios sociales, emergencias.
 *
 * @var string $titulo_categorias
 * @var string $subtitulo_categorias
 * @var array  $categorias_avisos
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_categorias    = $titulo_categorias ?? 'Categorias de Avisos';
$subtitulo_categorias = $subtitulo_categorias ?? 'Filtra los avisos por departamento o tema para encontrar lo que necesitas';

$categorias_avisos = $categorias_avisos ?? [
    [
        'nombre'      => 'Obras',
        'slug'        => 'obras',
        'descripcion' => 'Obras publicas, reurbanizaciones y mejoras de infraestructura',
        'cantidad'    => 8,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'color_desde' => '#F97316',
        'color_hasta' => '#EA580C',
    ],
    [
        'nombre'      => 'Trafico',
        'slug'        => 'trafico',
        'descripcion' => 'Cortes de trafico, desvios y regulacion viaria',
        'cantidad'    => 5,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'color_desde' => '#EF4444',
        'color_hasta' => '#DC2626',
    ],
    [
        'nombre'      => 'Medio Ambiente',
        'slug'        => 'medio-ambiente',
        'descripcion' => 'Calidad del aire, parques, zonas verdes y sostenibilidad',
        'cantidad'    => 6,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color_desde' => '#22C55E',
        'color_hasta' => '#16A34A',
    ],
    [
        'nombre'      => 'Cultura',
        'slug'        => 'cultura',
        'descripcion' => 'Actividades culturales, eventos y programacion municipal',
        'cantidad'    => 12,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2m0 2a2 2 0 100 4m0-4a2 2 0 110 4m10-4V2m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-9 6a2 2 0 100 4m0-4a2 2 0 110 4m9-4a2 2 0 100 4m0-4a2 2 0 110 4"/>',
        'color_desde' => '#A855F7',
        'color_hasta' => '#9333EA',
    ],
    [
        'nombre'      => 'Servicios Sociales',
        'slug'        => 'servicios-sociales',
        'descripcion' => 'Ayudas, subvenciones, asistencia social y bienestar',
        'cantidad'    => 7,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
        'color_desde' => '#EC4899',
        'color_hasta' => '#DB2777',
    ],
    [
        'nombre'      => 'Emergencias',
        'slug'        => 'emergencias',
        'descripcion' => 'Alertas meteorologicas, proteccion civil y situaciones de emergencia',
        'cantidad'    => 2,
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>',
        'color_desde' => '#EF4444',
        'color_hasta' => '#B91C1C',
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

        <!-- Grid de categorias -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($categorias_avisos as $categoria_item) : ?>
                <a href="<?php echo esc_url('/avisos-municipales/?categoria=' . $categoria_item['slug']); ?>"
                   class="group bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 text-center block">
                    <!-- Icono -->
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-transform duration-300 group-hover:scale-110"
                         style="background: linear-gradient(135deg, <?php echo esc_attr($categoria_item['color_desde']); ?>, <?php echo esc_attr($categoria_item['color_hasta']); ?>);">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $categoria_item['icono']; ?>
                        </svg>
                    </div>

                    <!-- Nombre -->
                    <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-red-600 transition-colors">
                        <?php echo esc_html($categoria_item['nombre']); ?>
                    </h3>

                    <!-- Descripcion -->
                    <p class="text-sm text-gray-500 mb-3 leading-relaxed">
                        <?php echo esc_html($categoria_item['descripcion']); ?>
                    </p>

                    <!-- Contador -->
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold"
                          style="background: <?php echo esc_attr($categoria_item['color_desde']); ?>15; color: <?php echo esc_attr($categoria_item['color_desde']); ?>;">
                        <?php echo esc_html($categoria_item['cantidad']); ?> <?php echo esc_html__('avisos', 'flavor-chat-ia'); ?>
                    </span>

                    <!-- Flecha -->
                    <div class="mt-4 flex items-center justify-center gap-1 text-sm font-medium text-gray-400 group-hover:text-red-600 transition-colors">
                        <?php echo esc_html__('Ver avisos', 'flavor-chat-ia'); ?>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
