<?php
/**
 * Template: Tarifas de Parkings
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Tarifas y Abonos';
$descripcion = $descripcion ?? 'Elige el plan que mejor se adapte a tus necesidades';

$planes = [
    ['nombre' => 'Por Horas', 'precio' => '2.50€', 'periodo' => '/hora', 'descripcion' => 'Paga solo lo que uses', 'caracteristicas' => ['Sin compromiso', 'Pago al salir', 'Primera hora fraccionable'], 'destacado' => false, 'color' => 'slate'],
    ['nombre' => 'Abono Dia', 'precio' => '12€', 'periodo' => '/dia', 'descripcion' => 'Ideal para visitas puntuales', 'caracteristicas' => ['24 horas completas', 'Entrada/salida ilimitada', 'Incluye lavado express'], 'destacado' => false, 'color' => 'slate'],
    ['nombre' => 'Abono Mensual', 'precio' => '89€', 'periodo' => '/mes', 'descripcion' => 'La opcion mas popular', 'caracteristicas' => ['Plaza reservada', 'Acceso 24/7', 'Carga vehiculo electrico', 'Seguro incluido'], 'destacado' => true, 'color' => 'blue'],
    ['nombre' => 'Abono Anual', 'precio' => '890€', 'periodo' => '/ano', 'descripcion' => 'Maximo ahorro garantizado', 'caracteristicas' => ['2 meses gratis', 'Plaza premium', 'Todos los servicios', 'Invitados gratis'], 'destacado' => false, 'color' => 'slate'],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #475569 0%, #334155 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo esc_html__('Tarifas', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($planes as $plan): ?>
                <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border-2 <?php echo $plan['destacado'] ? 'border-blue-500 scale-105' : 'border-gray-100'; ?> overflow-hidden">
                    <?php if ($plan['destacado']): ?>
                        <div class="absolute top-0 left-0 right-0 py-2 text-center text-sm font-bold text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <?php echo esc_html__('MAS POPULAR', 'flavor-chat-ia'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-6 <?php echo $plan['destacado'] ? 'pt-12' : ''; ?>">
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo esc_html($plan['nombre']); ?></h3>
                        <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($plan['descripcion']); ?></p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold <?php echo $plan['destacado'] ? 'text-blue-600' : 'text-gray-900'; ?>"><?php echo esc_html($plan['precio']); ?></span>
                            <span class="text-gray-500"><?php echo esc_html($plan['periodo']); ?></span>
                        </div>

                        <ul class="space-y-3 mb-6">
                            <?php foreach ($plan['caracteristicas'] as $caracteristica): ?>
                                <li class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-5 h-5 flex-shrink-0 <?php echo $plan['destacado'] ? 'text-blue-500' : 'text-green-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <?php echo esc_html($caracteristica); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="#contratar-<?php echo sanitize_title($plan['nombre']); ?>" class="block w-full py-3 rounded-xl text-center font-semibold transition-all hover:scale-105 <?php echo $plan['destacado'] ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>" style="<?php echo $plan['destacado'] ? 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);' : ''; ?>">
                            <?php echo esc_html__('Contratar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Info adicional -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6 bg-slate-50 rounded-2xl">
                <div class="inline-flex items-center justify-center p-3 rounded-xl bg-slate-100 text-slate-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo esc_html__('Seguridad 24/7', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Camaras de vigilancia y personal de seguridad las 24 horas', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center p-6 bg-slate-50 rounded-2xl">
                <div class="inline-flex items-center justify-center p-3 rounded-xl bg-slate-100 text-slate-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo esc_html__('Carga Electrica', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Puntos de carga para vehiculos electricos en todas las plantas', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center p-6 bg-slate-50 rounded-2xl">
                <div class="inline-flex items-center justify-center p-3 rounded-xl bg-slate-100 text-slate-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?php echo esc_html__('Pago Facil', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Acepta todas las tarjetas, pago movil y facturacion automatica', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>
</section>
