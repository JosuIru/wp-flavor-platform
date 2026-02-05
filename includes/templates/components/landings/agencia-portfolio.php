<?php
/**
 * Template: Portfolio Agencia
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$proyectos = [
    ['nombre' => 'Rebrand Fintech', 'categoria' => 'Branding', 'color_desde' => 'blue-500', 'color_hasta' => 'cyan-500'],
    ['nombre' => 'App Fitness', 'categoria' => 'UI/UX Design', 'color_desde' => 'green-500', 'color_hasta' => 'emerald-500'],
    ['nombre' => 'E-commerce Moda', 'categoria' => 'Desarrollo Web', 'color_desde' => 'pink-500', 'color_hasta' => 'rose-500'],
    ['nombre' => 'Dashboard SaaS', 'categoria' => 'Producto Digital', 'color_desde' => 'violet-500', 'color_hasta' => 'purple-500'],
    ['nombre' => 'Campaña Social', 'categoria' => 'Marketing Digital', 'color_desde' => 'amber-500', 'color_hasta' => 'orange-500'],
    ['nombre' => 'Web Corporativa', 'categoria' => 'Diseño Web', 'color_desde' => 'slate-500', 'color_hasta' => 'gray-500'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-24 bg-black">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-12">
            <div>
                <span class="inline-block text-pink-500 text-sm font-semibold mb-4 tracking-wider uppercase">Portfolio</span>
                <h2 class="text-3xl md:text-4xl font-bold text-white"><?php echo esc_html($titulo ?? 'Proyectos Destacados'); ?></h2>
            </div>
            <a href="#" class="mt-4 md:mt-0 inline-flex items-center text-white/70 hover:text-white transition-colors">
                Ver todos los proyectos
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($proyectos as $proyecto): ?>
            <div class="group relative rounded-2xl overflow-hidden cursor-pointer">
                <div class="aspect-[4/3] bg-gradient-to-br from-<?php echo $proyecto['color_desde']; ?> to-<?php echo $proyecto['color_hasta']; ?>"></div>
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
                    <span class="text-white/70 text-sm mb-2"><?php echo esc_html($proyecto['categoria']); ?></span>
                    <h3 class="text-white text-xl font-bold"><?php echo esc_html($proyecto['nombre']); ?></h3>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 py-12 border-t border-white/10">
            <div class="text-center">
                <div class="text-4xl font-bold text-white mb-2">150+</div>
                <div class="text-gray-500">Proyectos Completados</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-white mb-2">98%</div>
                <div class="text-gray-500">Clientes Satisfechos</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-white mb-2">8</div>
                <div class="text-gray-500">Premios de Diseño</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-white mb-2">12</div>
                <div class="text-gray-500">Años de Experiencia</div>
            </div>
        </div>
    </div>
</section>
