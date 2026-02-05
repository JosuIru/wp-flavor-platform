<?php
/**
 * Template: Grid de Planes de Membresia
 *
 * Tarjetas de precios con 3 planes: Basico, Premium y Pro.
 * Cada plan muestra precio, caracteristicas y boton de accion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$planes_membresia = [
    [
        'nombre'          => 'Basico',
        'precio'          => '0',
        'periodo'         => 'Gratis para siempre',
        'destacado'       => false,
        'caracteristicas' => [
            ['texto' => 'Acceso a noticias y novedades', 'incluido' => true],
            ['texto' => 'Participacion en foros',         'incluido' => true],
            ['texto' => 'Newsletter mensual',             'incluido' => true],
            ['texto' => 'Descuentos en comercios',        'incluido' => false],
            ['texto' => 'Eventos exclusivos',             'incluido' => false],
            ['texto' => 'Voto en asambleas',              'incluido' => false],
        ],
        'boton_texto'     => 'Registrarse Gratis',
        'color_boton'     => 'bg-gray-800 hover:bg-gray-900',
    ],
    [
        'nombre'          => 'Premium',
        'precio'          => '5',
        'periodo'         => 'al mes',
        'destacado'       => true,
        'caracteristicas' => [
            ['texto' => 'Todo del plan Basico',           'incluido' => true],
            ['texto' => 'Descuentos en comercios (15%)',  'incluido' => true],
            ['texto' => 'Eventos exclusivos',             'incluido' => true],
            ['texto' => 'Acceso prioritario actividades', 'incluido' => true],
            ['texto' => 'Voto en asambleas',              'incluido' => true],
            ['texto' => 'Asesoria personalizada',         'incluido' => false],
        ],
        'boton_texto'     => 'Elegir Premium',
        'color_boton'     => 'bg-rose-500 hover:bg-rose-600',
    ],
    [
        'nombre'          => 'Pro',
        'precio'          => '15',
        'periodo'         => 'al mes',
        'destacado'       => false,
        'caracteristicas' => [
            ['texto' => 'Todo del plan Premium',          'incluido' => true],
            ['texto' => 'Descuentos en comercios (25%)',  'incluido' => true],
            ['texto' => 'Asesoria personalizada',         'incluido' => true],
            ['texto' => 'Sala VIP en eventos',            'incluido' => true],
            ['texto' => 'Mencion como patrocinador',      'incluido' => true],
            ['texto' => 'Acceso anticipado novedades',    'incluido' => true],
        ],
        'boton_texto'     => 'Elegir Pro',
        'color_boton'     => 'bg-gray-800 hover:bg-gray-900',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Elige tu Plan'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Encuentra el plan que mejor se adapte a tus necesidades. Puedes cambiar o cancelar en cualquier momento.'); ?>
            </p>
            <div class="w-20 h-1 bg-rose-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de planes -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
            <?php foreach ($planes_membresia as $plan_actual): ?>
                <div class="relative bg-white rounded-2xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden <?php echo $plan_actual['destacado'] ? 'ring-2 ring-rose-500 scale-105 md:scale-110' : ''; ?>">
                    <!-- Badge recomendado -->
                    <?php if ($plan_actual['destacado']): ?>
                        <div class="bg-rose-500 text-white text-center py-2 text-sm font-bold uppercase tracking-wide">
                            <?php echo esc_html__('Recomendado', 'flavor-chat-ia'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-8">
                        <!-- Nombre del plan -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($plan_actual['nombre']); ?>
                        </h3>

                        <!-- Precio -->
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <?php if ($plan_actual['precio'] === '0'): ?>
                                    <span class="text-4xl font-bold text-gray-900"><?php echo esc_html__('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php else: ?>
                                    <span class="text-4xl font-bold text-gray-900"><?php echo esc_html($plan_actual['precio']); ?>&euro;</span>
                                    <span class="text-gray-500 ml-2">/<?php echo esc_html__('mes', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-400 mt-1">
                                <?php echo esc_html($plan_actual['periodo']); ?>
                            </p>
                        </div>

                        <!-- Lista de caracteristicas -->
                        <ul class="space-y-3 mb-8">
                            <?php foreach ($plan_actual['caracteristicas'] as $caracteristica_plan): ?>
                                <li class="flex items-center">
                                    <?php if ($caracteristica_plan['incluido']): ?>
                                        <svg class="w-5 h-5 text-rose-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-700"><?php echo esc_html($caracteristica_plan['texto']); ?></span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-gray-300 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-400"><?php echo esc_html($caracteristica_plan['texto']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Boton CTA -->
                        <a href="/socios/unirme/?plan=<?php echo esc_attr(sanitize_title($plan_actual['nombre'])); ?>" class="block w-full <?php echo esc_attr($plan_actual['color_boton']); ?> text-white py-3 rounded-xl font-semibold transition duration-300 transform hover:scale-[1.02] shadow-md text-center">
                            <?php echo esc_html($plan_actual['boton_texto']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Nota inferior -->
        <p class="text-center text-sm text-gray-400 mt-8">
            <?php echo esc_html__('Todos los planes incluyen soporte por email. IVA no incluido en planes de pago.', 'flavor-chat-ia'); ?>
        </p>
    </div>
</section>
