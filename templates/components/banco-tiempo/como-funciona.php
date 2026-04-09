<?php
/**
 * Template: Como Funciona - Banco de Tiempo
 *
 * Seccion explicativa con los 3 pasos para participar en el banco de tiempo.
 * Paso 1: Ofrece un servicio, Paso 2: Encuentra lo que necesitas, Paso 3: Intercambia tiempo.
 *
 * @var string $titulo_seccion
 * @var string $subtitulo_seccion
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion    = $titulo_seccion ?? 'Como Funciona el Banco de Tiempo';
$subtitulo_seccion = $subtitulo_seccion ?? 'Tres pasos sencillos para empezar a intercambiar servicios con tu comunidad';

$pasos_banco_tiempo = $pasos_banco_tiempo ?? [
    [
        'numero'      => '1',
        'titulo'      => 'Ofrece un Servicio',
        'descripcion' => 'Registra las habilidades y servicios que puedes ofrecer a tu comunidad. Cada hora de servicio equivale a un credito de tiempo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
        'color_desde' => '#F59E0B',
        'color_hasta' => '#D97706',
    ],
    [
        'numero'      => '2',
        'titulo'      => 'Encuentra lo que Necesitas',
        'descripcion' => 'Explora el catalogo de servicios disponibles. Desde clases particulares hasta reparaciones del hogar.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'color_desde' => '#EAB308',
        'color_hasta' => '#CA8A04',
    ],
    [
        'numero'      => '3',
        'titulo'      => 'Intercambia Tiempo',
        'descripcion' => 'Usa tus creditos de tiempo para recibir servicios. Sin dinero de por medio, solo tiempo y habilidades compartidas.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>',
        'color_desde' => '#F97316',
        'color_hasta' => '#EA580C',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24 bg-white">
    <div class="flavor-container">
        <!-- Titulo -->
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-6"></div>
        </div>

        <!-- Pasos -->
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Linea conectora (desktop) -->
                <div class="hidden md:block absolute top-12 left-0 right-0 h-1 bg-amber-200" style="width: calc(100% - 8rem); margin-left: 4rem;"></div>

                <?php foreach ($pasos_banco_tiempo as $paso_item) : ?>
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Numero -->
                            <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl mb-6 transform hover:scale-110 transition duration-300"
                                 style="background: linear-gradient(135deg, <?php echo esc_attr($paso_item['color_desde']); ?>, <?php echo esc_attr($paso_item['color_hasta']); ?>);">
                                <span class="text-3xl font-bold text-white"><?php echo esc_html($paso_item['numero']); ?></span>
                            </div>

                            <!-- Icono -->
                            <div class="mb-4">
                                <svg class="w-12 h-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $paso_item['icono']; ?>
                                </svg>
                            </div>

                            <!-- Titulo -->
                            <h3 class="text-xl font-bold text-gray-900 mb-3">
                                <?php echo esc_html($paso_item['titulo']); ?>
                            </h3>

                            <!-- Texto -->
                            <p class="text-gray-600 leading-relaxed">
                                <?php echo esc_html($paso_item['descripcion']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Beneficios adicionales -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-amber-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-amber-600 mb-2"><?php echo esc_html__('0', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>&euro;</div>
                    <div class="text-gray-700"><?php echo esc_html__('Sin Dinero de Por Medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-yellow-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600 mb-2">1:1</div>
                    <div class="text-gray-700"><?php echo esc_html__('1 Hora = 1 Credito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-orange-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-2">100%</div>
                    <div class="text-gray-700"><?php echo esc_html__('Basado en Confianza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
