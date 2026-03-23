<?php
/**
 * Template: Testimonios de Socios
 *
 * Tarjetas de testimonios de miembros de la comunidad.
 * Muestra nombre, texto, tipo de socio y avatar.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$testimonios_socios = $testimonios ?? [
    [
        'nombre'     => 'Maria Gonzalez',
        'texto'      => 'Desde que soy socia he conocido gente increible y he ahorrado mucho en mis compras locales. Los eventos exclusivos son fantasticos y el trato es siempre cercano.',
        'tipo_socio' => 'Socia Premium',
        'avatar'     => '',
    ],
    [
        'nombre'     => 'Carlos Ruiz',
        'texto'      => 'El plan Familiar nos ha permitido disfrutar de actividades con los ninos a precios reducidos. Merece mucho la pena, lo recomiendo a todas las familias del barrio.',
        'tipo_socio' => 'Socio Familiar',
        'avatar'     => '',
    ],
    [
        'nombre'     => 'Laura Martinez',
        'texto'      => 'Empece con el plan Basico y enseguida me pase a Premium. Los descuentos en comercios locales ya cubren de sobra la cuota mensual. Una gran comunidad.',
        'tipo_socio' => 'Socia Premium',
        'avatar'     => '',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Lo Que Dicen Nuestros Miembros'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Descubre por que miles de personas ya forman parte de nuestra comunidad.'); ?>
            </p>
            <div class="w-20 h-1 bg-rose-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de testimonios -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php foreach ($testimonios_socios as $testimonio_actual): ?>
                <div class="group bg-white rounded-xl p-6 border border-gray-100 hover:border-rose-200 shadow-sm hover:shadow-lg transition duration-300">
                    <!-- Comillas decorativas -->
                    <div class="mb-4">
                        <svg class="w-10 h-10 text-rose-200 group-hover:text-rose-300 transition duration-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H0z"/>
                        </svg>
                    </div>

                    <!-- Texto del testimonio -->
                    <blockquote class="text-gray-600 text-sm leading-relaxed mb-6">
                        &ldquo;<?php echo esc_html($testimonio_actual['texto']); ?>&rdquo;
                    </blockquote>

                    <!-- Datos del socio -->
                    <div class="flex items-center border-t border-gray-100 pt-4">
                        <!-- Avatar -->
                        <div class="w-12 h-12 bg-gradient-to-br from-rose-400 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0 mr-4">
                            <?php if (!empty($testimonio_actual['avatar'])): ?>
                                <img src="<?php echo esc_url($testimonio_actual['avatar']); ?>" alt="<?php echo esc_attr($testimonio_actual['nombre']); ?>" class="w-12 h-12 rounded-full object-cover">
                            <?php else: ?>
                                <span class="text-lg font-bold text-white">
                                    <?php echo esc_html(mb_substr($testimonio_actual['nombre'], 0, 1)); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Nombre y tipo -->
                        <div>
                            <div class="font-semibold text-gray-900">
                                <?php echo esc_html($testimonio_actual['nombre']); ?>
                            </div>
                            <div class="text-rose-500 text-sm font-medium">
                                <?php echo esc_html($testimonio_actual['tipo_socio']); ?>
                            </div>
                        </div>

                        <!-- Estrellas -->
                        <div class="ml-auto flex items-center">
                            <?php for ($indice_estrella_testimonio = 0; $indice_estrella_testimonio < 5; $indice_estrella_testimonio++): ?>
                                <svg class="w-4 h-4 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Estadistica de satisfaccion -->
        <div class="mt-14 max-w-2xl mx-auto bg-rose-50 rounded-xl p-6 text-center">
            <div class="flex items-center justify-center mb-2">
                <?php for ($indice_estrella_pie = 0; $indice_estrella_pie < 5; $indice_estrella_pie++): ?>
                    <svg class="w-6 h-6 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
            </div>
            <p class="text-rose-700 font-medium">
                <?php echo esc_html($texto_satisfaccion ?? 'El 96% de nuestros miembros recomiendan la membresia'); ?>
            </p>
        </div>
    </div>
</section>
