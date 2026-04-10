<?php
/**
 * Template: Categorias de Cursos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Explora por Categoria';
$descripcion = $descripcion ?? 'Encuentra el curso perfecto segun tus intereses';

$categorias = [
    ['nombre' => 'Tecnologia', 'descripcion' => 'Programacion, diseño web y apps', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>', 'cursos' => 45, 'color' => 'cyan'],
    ['nombre' => 'Idiomas', 'descripcion' => 'Ingles, frances, aleman y mas', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>', 'cursos' => 32, 'color' => 'sky'],
    ['nombre' => 'Manualidades', 'descripcion' => 'Costura, ceramica, pintura', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>', 'cursos' => 28, 'color' => 'pink'],
    ['nombre' => 'Cocina', 'descripcion' => 'Recetas, pasteleria, nutricion', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'cursos' => 24, 'color' => 'orange'],
    ['nombre' => 'Musica', 'descripcion' => 'Guitarra, piano, canto', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>', 'cursos' => 18, 'color' => 'violet'],
    ['nombre' => 'Salud y Bienestar', 'descripcion' => 'Yoga, meditacion, fitness', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>', 'cursos' => 22, 'color' => 'emerald'],
    ['nombre' => 'Negocios', 'descripcion' => 'Marketing, finanzas, emprendimiento', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>', 'cursos' => 35, 'color' => 'blue'],
    ['nombre' => 'Fotografia', 'descripcion' => 'Edicion, composicion, iluminacion', 'icono' => '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>', 'cursos' => 15, 'color' => 'indigo'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-cyan-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <?php echo esc_html__('Categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($categorias as $cat): ?>
                <a href="#categoria-<?php echo sanitize_title($cat['nombre']); ?>" class="group relative bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-2 overflow-hidden border border-gray-100">
                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center p-3 rounded-xl mb-4 transition-all duration-300 group-hover:scale-110 bg-<?php echo $cat['color']; ?>-100 text-<?php echo $cat['color']; ?>-600">
                            <?php echo $cat['icono']; ?>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-cyan-600 transition-colors"><?php echo esc_html($cat['nombre']); ?></h3>
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-cyan-100 text-cyan-700"><?php echo esc_html($cat['cursos']); ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4"><?php echo esc_html($cat['descripcion']); ?></p>
                        <div class="flex items-center gap-2 text-sm font-semibold text-cyan-600">
                            <span><?php echo esc_html__('Ver cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 text-center">
            <p class="text-lg text-gray-600 mb-6"><?php echo esc_html__('Total:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="font-bold text-cyan-600"><?php echo esc_html__('219 cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span> <?php echo esc_html__('disponibles en 8 categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="#todos-cursos" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%); color: white;">
                <span><?php echo esc_html__('Ver Todos los Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
