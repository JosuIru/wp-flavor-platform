<?php
/**
 * Template: Como Funciona - Tramites Online
 *
 * Seccion de 4 pasos explicando el proceso de tramitacion:
 * Buscar, Rellenar, Adjuntar y Recibir resolucion.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$pasos_tramitacion = [
    [
        'numero'      => 1,
        'titulo'      => 'Busca tu tramite',
        'descripcion' => 'Utiliza el buscador o navega por categorias para encontrar el tramite que necesitas realizar.',
        'icono'       => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'color_desde' => 'from-orange-400',
        'color_hasta' => 'to-orange-500',
    ],
    [
        'numero'      => 2,
        'titulo'      => 'Rellena el formulario',
        'descripcion' => 'Completa los datos requeridos en el formulario electronico con tus datos personales y la informacion necesaria.',
        'icono'       => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'color_desde' => 'from-amber-400',
        'color_hasta' => 'to-amber-500',
    ],
    [
        'numero'      => 3,
        'titulo'      => 'Adjunta documentacion',
        'descripcion' => 'Sube los documentos necesarios en formato digital. Aceptamos PDF, imagenes y documentos de texto.',
        'icono'       => 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13',
        'color_desde' => 'from-orange-500',
        'color_hasta' => 'to-amber-600',
    ],
    [
        'numero'      => 4,
        'titulo'      => 'Recibe resolucion',
        'descripcion' => 'Recibiras la notificacion y resolucion en tu buzun electronico. Consulta el estado en cualquier momento.',
        'icono'       => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'color_desde' => 'from-green-400',
        'color_hasta' => 'to-green-500',
    ],
];
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Como Funciona'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Realizar un tramite online es sencillo. Sigue estos cuatro pasos.'); ?>
            </p>
            <div class="w-20 h-1 bg-orange-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Pasos del proceso -->
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                <!-- Linea conectora horizontal (desktop) -->
                <div class="hidden md:block absolute top-16 left-0 right-0 h-0.5 bg-orange-200" style="width: calc(100% - 6rem); margin-left: 3rem;"></div>

                <?php foreach ($pasos_tramitacion as $paso_tramite): ?>
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Circulo con icono -->
                            <div class="relative z-10 w-32 h-32 bg-gradient-to-br <?php echo esc_attr($paso_tramite['color_desde'] . ' ' . $paso_tramite['color_hasta']); ?> rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($paso_tramite['icono']); ?>" />
                                </svg>
                            </div>

                            <!-- Numero -->
                            <div class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-700 rounded-full text-sm font-bold mb-3">
                                <?php echo esc_html($paso_tramite['numero']); ?>
                            </div>

                            <!-- Titulo -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                <?php echo esc_html($paso_tramite['titulo']); ?>
                            </h3>

                            <!-- Descripcion -->
                            <p class="text-gray-600 text-sm leading-relaxed">
                                <?php echo esc_html($paso_tramite['descripcion']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Informacion adicional -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-orange-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-2">100%</div>
                    <div class="text-gray-700"><?php echo esc_html__('Digital y seguro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-amber-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-amber-600 mb-2">24/7</div>
                    <div class="text-gray-700"><?php echo esc_html__('Disponible siempre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-green-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2"><?php echo esc_html__('3 dias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="text-gray-700"><?php echo esc_html__('Resolucion media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
