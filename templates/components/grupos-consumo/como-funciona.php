<?php
/**
 * Template: Cómo Funciona
 *
 * Muestra los pasos explicativos del proceso de funcionamiento de los grupos de consumo.
 * Incluye 3 pasos: Únete, Realiza tu pedido, Recogida.
 *
 * @var array  $pasos_proceso Array con datos de los pasos
 * @var string $component_classes Clases CSS adicionales para el componente
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$pasos_proceso = $pasos_proceso ?? [
    [
        'numero'      => 1,
        'titulo'      => 'Únete a un Grupo',
        'descripcion' => 'Selecciona el grupo de consumo más cercano a tu ubicación y crea tu cuenta. Es rápido, gratuito y sin compromisos.',
        'icono'       => 'users',
    ],
    [
        'numero'      => 2,
        'titulo'      => 'Realiza tu Pedido',
        'descripcion' => 'Explora los productos disponibles de nuestros productores locales y añade a tu carrito lo que desees antes de la fecha límite.',
        'icono'       => 'shopping-cart',
    ],
    [
        'numero'      => 3,
        'titulo'      => 'Recogida',
        'descripcion' => 'Recoge tu pedido en el punto de distribución acordado. Los productos son frescos, locales y a mejor precio que en tienda.',
        'icono'       => 'truck',
    ],
];

$component_classes = $component_classes ?? '';
?>
<section class="flavor-component flavor-como-funciona py-16 lg:py-24 <?php echo esc_attr($component_classes); ?>">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html__('¿Cómo Funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="text-lg text-gray-600">
                <?php echo esc_html__('Es muy simple. Solo necesitas seguir estos tres pasos para empezar a disfrutar de productos locales y de calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Grid de pasos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12 mb-12">
            <?php foreach ($pasos_proceso as $index => $paso_item) : ?>
                <div class="flavor-paso-item relative">
                    <!-- Línea conectora (solo en escritorio) -->
                    <?php if ($index < count($pasos_proceso) - 1) : ?>
                        <div class="hidden md:block absolute top-20 left-1/2 w-full h-0.5 bg-gradient-to-r from-green-400 to-green-600 transform translate-y-1/2" style="width: calc(100% + 2rem); left: 50%; margin-left: 1rem;"></div>
                    <?php endif; ?>

                    <!-- Contenido del paso -->
                    <div class="relative z-10">
                        <!-- Número círcular -->
                        <div class="flex justify-center mb-8">
                            <div class="flavor-paso-numero relative w-24 h-24 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-lg">
                                <span class="text-4xl font-bold"><?php echo esc_html($paso_item['numero']); ?></span>
                                <div class="absolute inset-0 rounded-full border-4 border-green-300 opacity-50"></div>
                            </div>
                        </div>

                        <!-- Icono -->
                        <div class="text-center mb-6">
                            <?php
                            switch ($paso_item['icono']) {
                                case 'users':
                                    ?>
                                    <svg class="w-12 h-12 text-green-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM9 20H4v-2a6 6 0 0112 0v2H9z"/>
                                    </svg>
                                    <?php
                                    break;
                                case 'shopping-cart':
                                    ?>
                                    <svg class="w-12 h-12 text-green-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                    </svg>
                                    <?php
                                    break;
                                case 'truck':
                                    ?>
                                    <svg class="w-12 h-12 text-green-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <?php
                                    break;
                            }
                            ?>
                        </div>

                        <!-- Título y descripción -->
                        <h3 class="text-xl font-bold text-gray-900 text-center mb-3">
                            <?php echo esc_html($paso_item['titulo']); ?>
                        </h3>
                        <p class="text-gray-600 text-center leading-relaxed">
                            <?php echo esc_html($paso_item['descripcion']); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA final -->
        <div class="max-w-2xl mx-auto text-center">
            <div class="rounded-2xl bg-gradient-to-r from-green-50 to-emerald-50 p-8 lg:p-12 border-2 border-green-200">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html__('¿Listo para empezar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p class="text-gray-600 mb-8">
                    <?php echo esc_html__('Únete a nuestra comunidad de consumo responsable y disfruta de productos frescos a mejor precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <a href="<?php echo esc_url(apply_filters('flavor_cta_unirse_url', '/grupos-consumo/unirse/')); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-green-600 text-white font-semibold text-lg hover:bg-green-700 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <?php echo esc_html__('Unirme Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>
