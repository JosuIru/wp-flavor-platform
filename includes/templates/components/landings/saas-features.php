<?php
/**
 * Template: Features SaaS
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$funcionalidades = [
    [
        'icono' => '⚡',
        'titulo' => 'Automatización Inteligente',
        'descripcion' => 'Automatiza tareas repetitivas y ahorra horas de trabajo manual cada semana',
        'color' => 'amber'
    ],
    [
        'icono' => '📊',
        'titulo' => 'Analytics Avanzados',
        'descripcion' => 'Dashboards en tiempo real con métricas que importan para tu negocio',
        'color' => 'blue'
    ],
    [
        'icono' => '🔗',
        'titulo' => 'Integraciones Nativas',
        'descripcion' => 'Conecta con más de 100 herramientas que ya usas diariamente',
        'color' => 'green'
    ],
    [
        'icono' => '🛡️',
        'titulo' => 'Seguridad Enterprise',
        'descripcion' => 'SOC 2 Type II, GDPR compliant y encriptación end-to-end',
        'color' => 'red'
    ],
    [
        'icono' => '🌐',
        'titulo' => 'API Robusta',
        'descripcion' => 'REST API completa con webhooks y SDKs en múltiples lenguajes',
        'color' => 'purple'
    ],
    [
        'icono' => '💬',
        'titulo' => 'Soporte 24/7',
        'descripcion' => 'Equipo de expertos disponible en cualquier momento para ayudarte',
        'color' => 'pink'
    ],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-block bg-violet-100 text-violet-700 text-sm font-semibold px-4 py-2 rounded-full mb-4">Funcionalidades</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Todo lo que necesitas en un solo lugar'); ?></h2>
            <p class="text-gray-600 max-w-2xl mx-auto text-lg">Herramientas potentes diseñadas para equipos modernos</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($funcionalidades as $funcionalidad): ?>
            <div class="group bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-xl hover:border-violet-200 transition-all duration-300">
                <div class="w-14 h-14 bg-<?php echo $funcionalidad['color']; ?>-100 rounded-xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition-transform">
                    <?php echo $funcionalidad['icono']; ?>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3"><?php echo esc_html($funcionalidad['titulo']); ?></h3>
                <p class="text-gray-600 leading-relaxed"><?php echo esc_html($funcionalidad['descripcion']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-16 text-center">
            <a href="#" class="inline-flex items-center text-violet-600 font-semibold hover:text-violet-700 transition-colors">
                Ver todas las funcionalidades
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
