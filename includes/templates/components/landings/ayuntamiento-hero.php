<?php
/**
 * Template: Hero Ayuntamiento
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#1d4ed8';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <div class="max-w-6xl mx-auto px-6 py-16">
        <div class="flex items-center justify-between flex-wrap gap-6">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 bg-white rounded-xl flex items-center justify-center text-4xl shadow-lg">
                    🏛️
                </div>
                <div class="text-white">
                    <h1 class="text-3xl font-bold mb-1"><?php echo esc_html($titulo ?? 'Portal Ciudadano'); ?></h1>
                    <p class="text-blue-100"><?php echo esc_html($subtitulo ?? 'Tu ayuntamiento a un clic'); ?></p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="<?php echo esc_url(home_url('/ayuntamiento/tramites/')); ?>" class="bg-white text-blue-700 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-colors">
                    📋 Trámites
                </a>
                <a href="<?php echo esc_url(home_url('/ayuntamiento/cita-previa/')); ?>" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-500 transition-colors border border-blue-400">
                    📅 Cita Previa
                </a>
            </div>
        </div>

        <?php if (!empty($mostrar_buscador)): ?>
        <div class="mt-8">
            <form action="<?php echo esc_url(home_url('/ayuntamiento/buscar/')); ?>" method="get" class="flex gap-4">
                <div class="flex-1 relative">
                    <input type="text" name="q" placeholder="¿Qué trámite o información buscas?"
                           class="w-full pl-12 pr-4 py-4 rounded-xl text-gray-800 text-lg focus:outline-none focus:ring-4 focus:ring-white/30">
                    <svg class="w-6 h-6 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="bg-blue-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-blue-900 transition-colors">
                    Buscar
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>
