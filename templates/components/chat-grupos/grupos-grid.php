<?php
/**
 * Template: Chat Grupos Grid
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$busqueda_placeholder = $busqueda_placeholder ?? 'Buscar grupos por nombre o tema...';

$grupos_ejemplo = $grupos_ejemplo ?? [
    [
        'nombre'      => 'Runners de la Ciudad',
        'descripcion' => 'Grupo para corredores urbanos. Compartimos rutas, tiempos y quedamos para entrenar juntos cada semana.',
        'miembros'    => 87,
        'categoria'   => 'Deportes',
        'actividad'   => 'activo',
        'color'       => '#EC4899',
        'iniciales'   => 'RC',
    ],
    [
        'nombre'      => 'Amantes de la Cocina',
        'descripcion' => 'Recetas, trucos culinarios y recomendaciones de restaurantes locales. Cocinamos y compartimos.',
        'miembros'    => 134,
        'categoria'   => 'Gastronomia',
        'actividad'   => 'activo',
        'color'       => '#D946EF',
        'iniciales'   => 'AC',
    ],
    [
        'nombre'      => 'Developers Local',
        'descripcion' => 'Comunidad de desarrolladores. Hablamos de codigo, proyectos open source y eventos tech.',
        'miembros'    => 56,
        'categoria'   => 'Tecnologia',
        'actividad'   => 'poco activo',
        'color'       => '#A855F7',
        'iniciales'   => 'DL',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Barra de busqueda -->
        <div class="max-w-2xl mx-auto mb-10">
            <form class="relative" method="get">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="buscar_grupo" placeholder="<?php echo esc_attr($busqueda_placeholder); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-pink-300 focus:border-pink-400 shadow-sm" />
            </form>
        </div>

        <!-- Grid de grupos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($grupos_ejemplo as $grupo_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all overflow-hidden">
                    <div class="p-6">
                        <!-- Cabecera del grupo -->
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background: <?php echo esc_attr($grupo_item['color']); ?>;">
                                <?php echo esc_html($grupo_item['iniciales']); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-800 truncate"><?php echo esc_html($grupo_item['nombre']); ?></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-700">
                                        <?php echo esc_html($grupo_item['categoria']); ?>
                                    </span>
                                    <?php
                                    $indicador_color_clase = ($grupo_item['actividad'] === 'activo') ? 'bg-green-400' : 'bg-yellow-400';
                                    ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                        <span class="w-2 h-2 rounded-full <?php echo esc_attr($indicador_color_clase); ?>"></span>
                                        <?php echo esc_html(ucfirst($grupo_item['actividad'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Descripcion -->
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            <?php echo esc_html($grupo_item['descripcion']); ?>
                        </p>

                        <!-- Footer -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                                <span><?php echo esc_html($grupo_item['miembros']); ?> miembros</span>
                            </div>
                            <button class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-pink-500 text-white text-sm font-medium hover:bg-pink-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                <?php echo esc_html__('Unirme', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
