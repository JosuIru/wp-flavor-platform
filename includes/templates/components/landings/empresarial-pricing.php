<?php
/**
 * Template: Tabla de Precios
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$planes = [
    ['nombre' => 'Starter', 'precio' => '29', 'periodo' => '/mes', 'descripcion' => 'Para pequeños equipos', 'features' => ['5 usuarios', '10GB almacenamiento', 'Soporte email', 'API básica'], 'destacado' => false],
    ['nombre' => 'Professional', 'precio' => '79', 'periodo' => '/mes', 'descripcion' => 'Para empresas en crecimiento', 'features' => ['25 usuarios', '100GB almacenamiento', 'Soporte prioritario', 'API completa', 'Integraciones'], 'destacado' => true],
    ['nombre' => 'Enterprise', 'precio' => '199', 'periodo' => '/mes', 'descripcion' => 'Para grandes organizaciones', 'features' => ['Usuarios ilimitados', 'Almacenamiento ilimitado', 'Soporte 24/7', 'API avanzada', 'SLA garantizado', 'Personalización'], 'destacado' => false],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Planes y Precios'); ?></h2>
            <p class="text-gray-600">Elige el plan que mejor se adapte a tus necesidades</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($planes as $plan): ?>
            <div class="<?php echo $plan['destacado'] ? 'bg-blue-600 text-white scale-105' : 'bg-white'; ?> rounded-2xl p-8 shadow-lg border <?php echo $plan['destacado'] ? 'border-blue-500' : 'border-gray-100'; ?> relative">
                <?php if ($plan['destacado']): ?>
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-yellow-400 text-yellow-900 text-sm font-bold px-4 py-1 rounded-full">
                    Más Popular
                </div>
                <?php endif; ?>

                <h3 class="text-xl font-bold mb-2"><?php echo esc_html($plan['nombre']); ?></h3>
                <p class="<?php echo $plan['destacado'] ? 'text-blue-200' : 'text-gray-500'; ?> text-sm mb-4"><?php echo esc_html($plan['descripcion']); ?></p>

                <div class="mb-6">
                    <span class="text-4xl font-bold"><?php echo esc_html($plan['precio']); ?>€</span>
                    <span class="<?php echo $plan['destacado'] ? 'text-blue-200' : 'text-gray-500'; ?>"><?php echo esc_html($plan['periodo']); ?></span>
                </div>

                <ul class="space-y-3 mb-8">
                    <?php foreach ($plan['features'] as $feature): ?>
                    <li class="flex items-center gap-2">
                        <svg class="w-5 h-5 <?php echo $plan['destacado'] ? 'text-blue-200' : 'text-green-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php echo esc_html($feature); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <button class="w-full py-3 rounded-xl font-semibold transition-colors <?php echo $plan['destacado'] ? 'bg-white text-blue-600 hover:bg-blue-50' : 'bg-blue-600 text-white hover:bg-blue-700'; ?>">
                    Empezar Ahora
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
