<?php
/**
 * Template: Hero Empresarial
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$color_primario = $color_primario ?? '#1e40af';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-800 text-white py-24">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block bg-blue-500/20 text-blue-300 text-sm font-medium px-4 py-2 rounded-full mb-6">
                    🚀 Soluciones para tu negocio
                </span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    <?php echo esc_html($titulo ?? 'Soluciones Empresariales'); ?>
                </h1>
                <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                    <?php echo esc_html($subtitulo ?? 'Potencia tu negocio con tecnología de vanguardia'); ?>
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="<?php echo esc_url($url_boton_principal ?? '#contacto'); ?>"
                       class="inline-flex items-center px-8 py-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                        <?php echo esc_html($texto_boton_principal ?? 'Solicitar Demo'); ?>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <a href="#servicios" class="inline-flex items-center px-8 py-4 border-2 border-white/30 hover:border-white/60 text-white font-semibold rounded-xl transition-all">
                        Ver Servicios
                    </a>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-2xl opacity-30"></div>
                    <div class="relative bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-blue-300">500+</div>
                                <div class="text-sm text-blue-200">Clientes</div>
                            </div>
                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-green-300">98%</div>
                                <div class="text-sm text-green-200">Satisfacción</div>
                            </div>
                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-purple-300">24/7</div>
                                <div class="text-sm text-purple-200">Soporte</div>
                            </div>
                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-yellow-300">15+</div>
                                <div class="text-sm text-yellow-200">Años</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
