<?php
/**
 * Template: Trading IA Features
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_features = $titulo_features ?? 'Herramientas de Trading Inteligente';

$funcionalidades_trading = $funcionalidades_trading ?? [
    [
        'titulo'      => 'Analisis tecnico automatizado',
        'descripcion' => 'La IA analiza patrones de graficos, indicadores tecnicos y tendencias de mercado de forma continua.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'color'       => '#06B6D4',
    ],
    [
        'titulo'      => 'Senales en tiempo real',
        'descripcion' => 'Recibe alertas de compra y venta basadas en algoritmos de machine learning actualizados al instante.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'color'       => '#0891B2',
    ],
    [
        'titulo'      => 'Backtesting de estrategias',
        'descripcion' => 'Prueba tus estrategias con datos historicos antes de arriesgar capital real en el mercado.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#0D9488',
    ],
    [
        'titulo'      => 'Gestion de riesgo',
        'descripcion' => 'Controla tu exposicion con stop-loss inteligentes, trailing stops y limites de perdida configurables.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'color'       => '#14B8A6',
    ],
    [
        'titulo'      => 'Multi-mercado',
        'descripcion' => 'Opera en forex, criptomonedas, acciones, materias primas y mas desde una sola plataforma.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#22D3EE',
    ],
    [
        'titulo'      => 'Alertas personalizadas',
        'descripcion' => 'Configura notificaciones por precio, volumen, patrones o eventos de mercado a tu medida.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'color'       => '#67E8F9',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-900">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-3"><?php echo esc_html($titulo_features); ?></h2>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto"><?php echo esc_html__('Tecnologia avanzada para decisiones informadas en los mercados financieros', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($funcionalidades_trading as $funcionalidad_item) : ?>
                <div class="flavor-card bg-gray-800 rounded-2xl p-6 border border-gray-700 hover:border-cyan-500/30 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($funcionalidad_item['color']); ?>20;">
                        <svg class="w-6 h-6" style="color: <?php echo esc_attr($funcionalidad_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $funcionalidad_item['icono']; ?>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2"><?php echo esc_html($funcionalidad_item['titulo']); ?></h3>
                    <p class="text-sm text-gray-400 leading-relaxed"><?php echo esc_html($funcionalidad_item['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
