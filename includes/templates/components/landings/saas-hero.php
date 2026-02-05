<?php
/**
 * Template: Hero SaaS
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color_primario = $color_primario ?? '#7c3aed';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden bg-gradient-to-br from-violet-900 via-purple-900 to-indigo-900 py-24">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.08\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>

    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
            <div class="inline-flex items-center gap-2 bg-violet-500/20 text-violet-200 text-sm font-medium px-4 py-2 rounded-full mb-6 border border-violet-500/30">
                <span>🚀</span> La plataforma #1 en productividad
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                <?php echo esc_html($titulo ?? 'Transforma tu negocio con tecnología'); ?>
            </h1>
            <p class="text-xl text-violet-200 mb-8 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Automatiza procesos, mejora la colaboración y escala tu empresa'); ?>
            </p>
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <a href="#" class="inline-flex items-center px-8 py-4 bg-white text-violet-900 font-bold rounded-xl hover:bg-violet-100 transition-all shadow-lg shadow-violet-500/30">
                    Comenzar Prueba Gratis
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="#" class="inline-flex items-center px-8 py-4 bg-violet-500/20 text-white border border-violet-400/30 font-semibold rounded-xl hover:bg-violet-500/30 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Ver Demo
                </a>
            </div>

            <div class="flex flex-wrap justify-center items-center gap-8 text-violet-300 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    14 días gratis
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Sin tarjeta de crédito
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cancela cuando quieras
                </div>
            </div>
        </div>

        <div class="mt-16 bg-white/10 backdrop-blur-sm rounded-3xl p-8 border border-white/20">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-white mb-2">10K+</div>
                    <div class="text-violet-300">Empresas activas</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-white mb-2">99.9%</div>
                    <div class="text-violet-300">Uptime garantizado</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-white mb-2">50M+</div>
                    <div class="text-violet-300">Tareas completadas</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-white mb-2">4.9⭐</div>
                    <div class="text-violet-300">Satisfacción cliente</div>
                </div>
            </div>
        </div>
    </div>
</section>
