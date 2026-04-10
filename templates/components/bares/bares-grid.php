<?php
/**
 * Template: Grid de Bares y Restaurantes
 *
 * Listado de establecimientos en tarjetas con filtros por tipo
 * y valoraciones visibles.
 *
 * @var string $titulo_seccion
 * @var int    $columnas
 * @var string $tipo_filtro
 * @var bool   $mostrar_valoracion
 * @var string $component_classes
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$cantidad_columnas    = intval($columnas ?? 3);
$filtro_tipo_activo   = $tipo_filtro ?? 'todos';
$mostrar_estrellas    = !isset($mostrar_valoracion) || $mostrar_valoracion;

// Mapeo de columnas a clases de grid
$clases_grid_columnas = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-2 lg:grid-cols-3',
    4 => 'md:grid-cols-2 lg:grid-cols-4',
];
$clase_grid = $clases_grid_columnas[$cantidad_columnas] ?? 'md:grid-cols-2 lg:grid-cols-3';

// Datos de ejemplo (fallback) con bares realistas
$bares_ejemplo = [
    [
        'id'                => 1,
        'nombre'            => 'La Taberna del Puerto',
        'tipo'              => 'bar',
        'tipo_label'        => 'Bar',
        'descripcion'       => 'Tapas tradicionales y pinchos vascos en un ambiente marinero. Especialidad en anchoas del Cantabrico y txakoli.',
        'imagen'            => '',
        'direccion'         => 'Calle del Puerto 15, Casco Viejo',
        'telefono'          => '944 123 456',
        'valoracion_media'  => 4.7,
        'valoraciones_count'=> 124,
        'color_gradiente'   => 'from-amber-500 to-orange-600',
        'color_badge'       => 'text-amber-600',
        'caracteristicas'   => ['terraza', 'wifi', 'mascotas'],
    ],
    [
        'id'                => 2,
        'nombre'            => 'Trattoria da Marco',
        'tipo'              => 'restaurante',
        'tipo_label'        => 'Restaurante',
        'descripcion'       => 'Autentica cocina italiana con pasta fresca hecha a diario. Pizzas al horno de lena y carta de vinos selecta.',
        'imagen'            => '',
        'direccion'         => 'Plaza Nueva 8, Ensanche',
        'telefono'          => '944 234 567',
        'valoracion_media'  => 4.5,
        'valoraciones_count'=> 89,
        'color_gradiente'   => 'from-red-500 to-rose-600',
        'color_badge'       => 'text-red-600',
        'caracteristicas'   => ['accesible', 'reservas'],
    ],
    [
        'id'                => 3,
        'nombre'            => 'Cafe Central',
        'tipo'              => 'cafeteria',
        'tipo_label'        => 'Cafeteria',
        'descripcion'       => 'Cafe de especialidad, brunch y reposteria artesanal. Ambiente acogedor para trabajar o leer con wifi gratuito.',
        'imagen'            => '',
        'direccion'         => 'Gran Via 22, Centro',
        'telefono'          => '944 345 678',
        'valoracion_media'  => 4.8,
        'valoraciones_count'=> 203,
        'color_gradiente'   => 'from-violet-500 to-purple-600',
        'color_badge'       => 'text-violet-600',
        'caracteristicas'   => ['wifi', 'terraza', 'accesible'],
    ],
    [
        'id'                => 4,
        'nombre'            => 'The Irish Corner',
        'tipo'              => 'pub',
        'tipo_label'        => 'Pub',
        'descripcion'       => 'Pub irlandes con musica en vivo los fines de semana. Amplia seleccion de cervezas artesanales e importadas.',
        'imagen'            => '',
        'direccion'         => 'Calle Ledesma 5, Abando',
        'telefono'          => '944 456 789',
        'valoracion_media'  => 4.3,
        'valoraciones_count'=> 67,
        'color_gradiente'   => 'from-blue-500 to-indigo-600',
        'color_badge'       => 'text-blue-600',
        'caracteristicas'   => ['musica_en_vivo', 'wifi'],
    ],
    [
        'id'                => 5,
        'nombre'            => 'La Terraza del Sol',
        'tipo'              => 'terraza',
        'tipo_label'        => 'Terraza',
        'descripcion'       => 'Terraza panoramica con vistas a la ria. Cocina mediterranea, cocktails de autor y atardeceres espectaculares.',
        'imagen'            => '',
        'direccion'         => 'Muelle de Evaristo Churruca 1',
        'telefono'          => '944 567 890',
        'valoracion_media'  => 4.6,
        'valoraciones_count'=> 156,
        'color_gradiente'   => 'from-emerald-500 to-teal-600',
        'color_badge'       => 'text-emerald-600',
        'caracteristicas'   => ['terraza', 'vistas', 'accesible'],
    ],
    [
        'id'                => 6,
        'nombre'            => 'Cocteleria Noir',
        'tipo'              => 'cocteleria',
        'tipo_label'        => 'Cocteleria',
        'descripcion'       => 'Bar de cocktails de autor en ambiente speakeasy. Mixologia creativa con ingredientes locales y de temporada.',
        'imagen'            => '',
        'direccion'         => 'Calle Somera 12, Casco Viejo',
        'telefono'          => '944 678 901',
        'valoracion_media'  => 4.9,
        'valoraciones_count'=> 78,
        'color_gradiente'   => 'from-pink-500 to-fuchsia-600',
        'color_badge'       => 'text-pink-600',
        'caracteristicas'   => ['reservas', 'musica_en_vivo'],
    ],
];

// Iconos de caracteristicas
$iconos_caracteristicas = [
    'terraza'        => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
    'wifi'           => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>',
    'accesible'      => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'mascotas'       => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
    'reservas'       => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
    'musica_en_vivo' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>',
    'vistas'         => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
];

// Etiquetas de caracteristicas
$etiquetas_caracteristicas = [
    'terraza'        => __('Terraza', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'wifi'           => __('WiFi', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'accesible'      => __('Accesible', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'mascotas'       => __('Mascotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'reservas'       => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'musica_en_vivo' => __('Musica en vivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'vistas'         => __('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<section class="flavor-component flavor-section py-16 <?php echo esc_attr($component_classes ?? ''); ?>" style="background: linear-gradient(to bottom, #faf5ff, white);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black mb-4" style="color: var(--flavor-text, #111827);">
                    <?php echo esc_html($titulo_seccion ?? __('Establecimientos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                </h2>
                <p class="text-xl max-w-3xl mx-auto" style="color: var(--flavor-text-muted, #6b7280);">
                    <?php _e('Encuentra tu proximo lugar favorito entre nuestra seleccion de bares, restaurantes y mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Filtros por tipo -->
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <button class="px-6 py-2.5 rounded-full font-semibold text-sm transition-all duration-200 hover:shadow-md" style="background: var(--flavor-primary); color: white;">
                    <?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <?php
                $filtros_tipo_disponibles = [
                    'bar'         => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'restaurante' => __('Restaurantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'cafeteria'   => __('Cafeterias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'pub'         => __('Pubs', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'terraza'     => __('Terrazas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'cocteleria'  => __('Coctelerias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                foreach ($filtros_tipo_disponibles as $clave_filtro => $texto_filtro): ?>
                    <button class="px-6 py-2.5 bg-white rounded-full font-semibold text-sm border transition-all duration-200 hover:shadow-md" style="color: var(--flavor-text, #374151); border-color: #e5e7eb;">
                        <?php echo esc_html($texto_filtro); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid de bares -->
            <div class="grid <?php echo esc_attr($clase_grid); ?> gap-8">
                <?php foreach ($bares_ejemplo as $bar_datos): ?>
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                    <!-- Imagen del bar -->
                    <div class="relative h-52 bg-gradient-to-br <?php echo esc_attr($bar_datos['color_gradiente']); ?> overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                            </svg>
                        </div>
                        <!-- Badge tipo -->
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold <?php echo esc_attr($bar_datos['color_badge']); ?>">
                            <?php echo esc_html($bar_datos['tipo_label']); ?>
                        </div>
                        <?php if ($mostrar_estrellas): ?>
                        <!-- Valoracion -->
                        <div class="absolute top-4 right-4 px-3 py-1 bg-black/50 backdrop-blur-sm rounded-full text-xs font-bold text-white flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <?php echo esc_html(number_format($bar_datos['valoracion_media'], 1)); ?>
                            <span class="text-white/70">(<?php echo esc_html($bar_datos['valoraciones_count']); ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2 transition-colors" style="color: var(--flavor-text, #111827);">
                            <?php echo esc_html($bar_datos['nombre']); ?>
                        </h3>
                        <p class="text-sm mb-4 line-clamp-2" style="color: var(--flavor-text-muted, #6b7280);">
                            <?php echo esc_html($bar_datos['descripcion']); ?>
                        </p>

                        <!-- Info de ubicacion y telefono -->
                        <div class="flex flex-col gap-2 text-sm mb-4" style="color: var(--flavor-text-muted, #6b7280);">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate"><?php echo esc_html($bar_datos['direccion']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span><?php echo esc_html($bar_datos['telefono']); ?></span>
                            </div>
                        </div>

                        <!-- Caracteristicas -->
                        <div class="flex flex-wrap gap-2 mb-4 pb-4 border-b" style="border-color: #f3f4f6;">
                            <?php foreach ($bar_datos['caracteristicas'] as $clave_caracteristica): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" style="background: #f3f4f6; color: var(--flavor-text-muted, #6b7280);">
                                    <?php echo $iconos_caracteristicas[$clave_caracteristica] ?? ''; ?>
                                    <?php echo esc_html($etiquetas_caracteristicas[$clave_caracteristica] ?? ucfirst($clave_caracteristica)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center justify-between">
                            <a href="#ver-carta" class="text-sm font-semibold transition-colors" style="color: var(--flavor-primary);">
                                <?php _e('Ver carta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> &rarr;
                            </a>
                            <a href="#reservar" class="px-5 py-2 rounded-lg font-semibold text-sm text-white transition-all duration-200 hover:shadow-lg" style="background: var(--flavor-primary);">
                                <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cargar mas -->
            <div class="text-center mt-12">
                <button class="px-8 py-3 rounded-full font-bold text-white shadow-lg hover:shadow-xl transition-all duration-200" style="background: var(--flavor-primary);">
                    <?php _e('Ver mas establecimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>
</section>
