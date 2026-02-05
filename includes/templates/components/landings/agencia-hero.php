<?php
/**
 * Template: Hero Agencia
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color_primario = $color_primario ?? '#ec4899';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative min-h-screen flex items-center bg-black overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-pink-500/20 via-transparent to-purple-500/20"></div>
    <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-pink-500 rounded-full blur-[150px] opacity-30"></div>
    <div class="absolute bottom-1/4 left-1/4 w-96 h-96 bg-purple-500 rounded-full blur-[150px] opacity-30"></div>

    <div class="max-w-6xl mx-auto px-6 py-24 relative z-10 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center gap-2 bg-white/10 text-white/80 text-sm font-medium px-4 py-2 rounded-full mb-6 backdrop-blur-sm border border-white/10">
                    <span class="w-2 h-2 bg-pink-500 rounded-full animate-pulse"></span>
                    Agencia Creativa Digital
                </div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                    <?php echo esc_html($titulo ?? 'Diseñamos experiencias que inspiran'); ?>
                </h1>
                <p class="text-xl text-gray-300 mb-8">
                    <?php echo esc_html($subtitulo ?? 'Branding, diseño web y estrategia digital para marcas que quieren destacar'); ?>
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all shadow-lg shadow-pink-500/30">
                        Ver Portfolio
                    </a>
                    <a href="#" class="inline-flex items-center px-8 py-4 bg-white/10 text-white border border-white/20 font-semibold rounded-xl hover:bg-white/20 transition-all backdrop-blur-sm">
                        Contactar
                    </a>
                </div>

                <div class="mt-12 flex items-center gap-8">
                    <div>
                        <div class="text-3xl font-bold text-white">150+</div>
                        <div class="text-gray-400 text-sm">Proyectos</div>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div>
                        <div class="text-3xl font-bold text-white">50+</div>
                        <div class="text-gray-400 text-sm">Clientes</div>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div>
                        <div class="text-3xl font-bold text-white">8</div>
                        <div class="text-gray-400 text-sm">Premios</div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="bg-gradient-to-br from-pink-500 to-purple-500 rounded-2xl aspect-square"></div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl aspect-video border border-white/20"></div>
                    </div>
                    <div class="space-y-4 pt-8">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl aspect-video border border-white/20"></div>
                        <div class="bg-gradient-to-br from-purple-500 to-indigo-500 rounded-2xl aspect-square"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
