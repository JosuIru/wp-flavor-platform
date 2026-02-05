<?php
/**
 * Template: Hero Ofimática
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color_primario = $color_primario ?? '#0284c7';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden bg-gradient-to-br from-sky-50 via-white to-blue-50 py-24">
    <div class="absolute top-0 right-0 w-96 h-96 bg-sky-200 rounded-full blur-3xl opacity-30 -translate-y-1/2 translate-x-1/2"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-200 rounded-full blur-3xl opacity-30 translate-y-1/2 -translate-x-1/2"></div>

    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 bg-sky-100 text-sky-700 text-sm font-medium px-4 py-2 rounded-full mb-6">
                <span>📄</span> Suite de Productividad
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-800 mb-6">
                <?php echo esc_html($titulo ?? 'Suite de Productividad'); ?>
            </h1>
            <p class="text-xl text-gray-600 mb-8">
                <?php echo esc_html($subtitulo ?? 'Documentos, hojas de cálculo y presentaciones en la nube'); ?>
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="#" class="inline-flex items-center px-8 py-4 bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl transition-all shadow-lg shadow-sky-500/30">
                    Empezar Gratis
                </a>
                <a href="#" class="inline-flex items-center px-8 py-4 bg-white border-2 border-gray-200 hover:border-sky-300 text-gray-700 font-semibold rounded-xl transition-all">
                    Ver Demo
                </a>
            </div>
        </div>

        <div class="mt-16 grid grid-cols-3 md:grid-cols-6 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">📝</div>
                <div class="text-sm font-medium text-gray-700">Documentos</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">📊</div>
                <div class="text-sm font-medium text-gray-700">Hojas</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">📽️</div>
                <div class="text-sm font-medium text-gray-700">Presentaciones</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">📅</div>
                <div class="text-sm font-medium text-gray-700">Calendario</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">✉️</div>
                <div class="text-sm font-medium text-gray-700">Email</div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hover:shadow-lg transition-shadow">
                <div class="text-3xl mb-2">💾</div>
                <div class="text-sm font-medium text-gray-700">Drive</div>
            </div>
        </div>
    </div>
</section>
