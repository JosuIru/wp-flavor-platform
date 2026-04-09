<?php
/**
 * Template: Tipos de Membresia
 *
 * Tarjetas de precios para los tipos de membresia:
 * Basico, Premium y Familiar. Cada una con nombre,
 * precio, lista de caracteristicas y boton de accion.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$tipos_membresia = [
    [
        'nombre'          => 'Basico',
        'precio'          => '0',
        'periodo'         => 'Gratis para siempre',
        'destacado'       => false,
        'descripcion'     => 'Ideal para empezar a disfrutar de la comunidad sin coste alguno.',
        'caracteristicas' => [
            ['texto' => 'Acceso a noticias y novedades', 'incluido' => true],
            ['texto' => 'Participacion en foros',         'incluido' => true],
            ['texto' => 'Newsletter mensual',             'incluido' => true],
            ['texto' => 'Descuentos en comercios',        'incluido' => false],
            ['texto' => 'Eventos exclusivos',             'incluido' => false],
            ['texto' => 'Acceso prioritario',             'incluido' => false],
        ],
        'boton_texto'     => 'Registrarse Gratis',
        'boton_url'       => '/socios/unirme/',
        'color_boton'     => 'bg-gray-800 hover:bg-gray-900',
    ],
    [
        'nombre'          => 'Premium',
        'precio'          => '9',
        'periodo'         => 'al mes',
        'destacado'       => true,
        'descripcion'     => 'La opcion mas popular. Accede a todos los beneficios exclusivos.',
        'caracteristicas' => [
            ['texto' => 'Todo del plan Basico',           'incluido' => true],
            ['texto' => 'Descuentos en comercios (20%)',  'incluido' => true],
            ['texto' => 'Eventos exclusivos',             'incluido' => true],
            ['texto' => 'Acceso prioritario actividades', 'incluido' => true],
            ['texto' => 'Voto en asambleas',              'incluido' => true],
            ['texto' => 'Beneficios para familia',        'incluido' => false],
        ],
        'boton_texto'     => 'Unirme Ahora',
        'boton_url'       => '/socios/unirme/',
        'color_boton'     => 'bg-rose-500 hover:bg-rose-600',
    ],
    [
        'nombre'          => 'Familiar',
        'precio'          => '15',
        'periodo'         => 'al mes',
        'destacado'       => false,
        'descripcion'     => 'Para toda la familia. Incluye hasta 4 miembros adicionales.',
        'caracteristicas' => [
            ['texto' => 'Todo del plan Premium',          'incluido' => true],
            ['texto' => 'Descuentos en comercios (25%)',  'incluido' => true],
            ['texto' => 'Hasta 4 miembros adicionales',   'incluido' => true],
            ['texto' => 'Actividades familiares',         'incluido' => true],
            ['texto' => 'Descuentos en campamentos',      'incluido' => true],
            ['texto' => 'Acceso anticipado novedades',    'incluido' => true],
        ],
        'boton_texto'     => 'Unirme Ahora',
        'boton_url'       => '/socios/unirme/',
        'color_boton'     => 'bg-gray-800 hover:bg-gray-900',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Tipos de Membresia'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Elige el tipo de membresia que mejor se adapte a ti y a tu familia.'); ?>
            </p>
            <div class="w-20 h-1 bg-rose-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de tipos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto items-start">
            <?php foreach ($tipos_membresia as $tipo_actual): ?>
                <div class="relative bg-white rounded-2xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden <?php echo $tipo_actual['destacado'] ? 'ring-2 ring-rose-500 scale-105 md:scale-110' : ''; ?>">
                    <!-- Badge recomendado -->
                    <?php if ($tipo_actual['destacado']): ?>
                        <div class="bg-rose-500 text-white text-center py-2 text-sm font-bold uppercase tracking-wide">
                            <?php echo esc_html__('Recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-8">
                        <!-- Nombre del tipo -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            <?php echo esc_html($tipo_actual['nombre']); ?>
                        </h3>

                        <!-- Descripcion breve -->
                        <p class="text-sm text-gray-500 mb-4">
                            <?php echo esc_html($tipo_actual['descripcion']); ?>
                        </p>

                        <!-- Precio -->
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <?php if ($tipo_actual['precio'] === '0'): ?>
                                    <span class="text-4xl font-bold text-gray-900"><?php echo esc_html__('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="text-4xl font-bold text-gray-900"><?php echo esc_html($tipo_actual['precio']); ?>&euro;</span>
                                    <span class="text-gray-500 ml-2">/<?php echo esc_html__('mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-400 mt-1">
                                <?php echo esc_html($tipo_actual['periodo']); ?>
                            </p>
                        </div>

                        <!-- Lista de caracteristicas -->
                        <ul class="space-y-3 mb-8">
                            <?php foreach ($tipo_actual['caracteristicas'] as $caracteristica_tipo): ?>
                                <li class="flex items-center">
                                    <?php if ($caracteristica_tipo['incluido']): ?>
                                        <svg class="w-5 h-5 text-rose-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-700"><?php echo esc_html($caracteristica_tipo['texto']); ?></span>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-gray-300 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-400"><?php echo esc_html($caracteristica_tipo['texto']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Boton CTA -->
                        <a href="<?php echo esc_url($tipo_actual['boton_url']); ?>"
                           class="block w-full <?php echo esc_attr($tipo_actual['color_boton']); ?> text-white py-3 rounded-xl font-semibold text-center transition duration-300 transform hover:scale-[1.02] shadow-md">
                            <?php echo esc_html($tipo_actual['boton_texto']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Nota inferior -->
        <p class="text-center text-sm text-gray-400 mt-8">
            <?php echo esc_html__('Todos los planes se pueden cancelar en cualquier momento. IVA no incluido en planes de pago.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>
</section>
