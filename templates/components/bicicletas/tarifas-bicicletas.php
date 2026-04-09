<?php
/**
 * Template: Tarifas de Bicicletas Compartidas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Planes y Tarifas';
$descripcion = $descripcion ?? 'Elige el plan que mejor se adapte a tu estilo de vida';

$planes = [
    ['nombre' => 'Ocasional', 'precio' => '1€', 'periodo' => '/viaje', 'descripcion' => 'Perfecto para uso esporadico', 'caracteristicas' => ['30 minutos incluidos', 'Sin registro previo', 'Pago por uso'], 'destacado' => false],
    ['nombre' => 'Semanal', 'precio' => '5€', 'periodo' => '/semana', 'descripcion' => 'Ideal para turistas y visitantes', 'caracteristicas' => ['Viajes ilimitados', '45 min por viaje', 'Bicis electricas +0.50€'], 'destacado' => false],
    ['nombre' => 'Mensual', 'precio' => '15€', 'periodo' => '/mes', 'descripcion' => 'La opcion mas elegida', 'caracteristicas' => ['Viajes ilimitados', '60 min por viaje', 'Bicis electricas incluidas', 'App premium'], 'destacado' => true],
    ['nombre' => 'Anual', 'precio' => '99€', 'periodo' => '/ano', 'descripcion' => 'Maximo ahorro para usuarios frecuentes', 'caracteristicas' => ['Todo incluido', '90 min por viaje', 'Prioridad reserva', 'Descuentos partners'], 'destacado' => false],
];

$beneficios = [
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Ecologico', 'desc' => '0 emisiones de CO2'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Rapido', 'desc' => 'Evita atascos'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>', 'titulo' => 'Saludable', 'desc' => 'Ejercicio diario'],
    ['icono' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', 'titulo' => 'Economico', 'desc' => 'Ahorra en transporte'],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo esc_html__('Tarifas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Beneficios -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <?php foreach ($beneficios as $ben): ?>
                <div class="text-center p-4 bg-lime-50 rounded-xl">
                    <div class="inline-flex items-center justify-center p-3 rounded-xl bg-lime-100 text-lime-600 mb-3">
                        <?php echo $ben['icono']; ?>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($ben['titulo']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html($ben['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Planes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($planes as $plan): ?>
                <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border-2 <?php echo $plan['destacado'] ? 'border-lime-500 scale-105' : 'border-gray-100'; ?> overflow-hidden">
                    <?php if ($plan['destacado']): ?>
                        <div class="absolute top-0 left-0 right-0 py-2 text-center text-sm font-bold text-white" style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                            <?php echo esc_html__('MAS POPULAR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-6 <?php echo $plan['destacado'] ? 'pt-12' : ''; ?>">
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo esc_html($plan['nombre']); ?></h3>
                        <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($plan['descripcion']); ?></p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold <?php echo $plan['destacado'] ? 'text-lime-600' : 'text-gray-900'; ?>"><?php echo esc_html($plan['precio']); ?></span>
                            <span class="text-gray-500"><?php echo esc_html($plan['periodo']); ?></span>
                        </div>

                        <ul class="space-y-3 mb-6">
                            <?php foreach ($plan['caracteristicas'] as $caracteristica): ?>
                                <li class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-5 h-5 flex-shrink-0 <?php echo $plan['destacado'] ? 'text-lime-500' : 'text-green-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <?php echo esc_html($caracteristica); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="#contratar-<?php echo sanitize_title($plan['nombre']); ?>" class="block w-full py-3 rounded-xl text-center font-semibold transition-all hover:scale-105 <?php echo $plan['destacado'] ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>" style="<?php echo $plan['destacado'] ? 'background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);' : ''; ?>">
                            <?php echo esc_html__('Elegir Plan', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center text-sm text-gray-500 mt-8"><?php echo esc_html__('Todos los planes se renuevan automaticamente. Puedes cancelar en cualquier momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
</section>
