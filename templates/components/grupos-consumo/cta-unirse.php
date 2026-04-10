<?php
/**
 * Template: CTA Unirse - Grupos de Consumo
 *
 * Seccion de llamada a la accion para unirse a un grupo de consumo existente
 * o crear uno nuevo en tu barrio.
 *
 * @var string $titulo_unirse
 * @var string $descripcion_unirse
 * @var string $url_unirse_grupo
 * @var string $url_crear_grupo
 * @var array  $beneficios_unirse
 * @var string $component_classes
 *
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_unirse      = $titulo_unirse ?? 'Unete a un Grupo de Consumo';
$descripcion_unirse = $descripcion_unirse ?? 'Forma parte de un grupo que compra directamente a productores locales. Mejor precio, mejor calidad y apoyo a la economia local.';
$url_unirse_grupo   = $url_unirse_grupo ?? '/grupos-consumo/unirse/';
$url_crear_grupo    = $url_crear_grupo ?? '/grupos-consumo/crear/';

$beneficios_unirse = $beneficios_unirse ?? [
    'Productos frescos directos del productor',
    'Precios mas bajos gracias a la compra colectiva',
    'Apoyas la economia local y el comercio justo',
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 50%, #ECFDF5 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Ilustracion -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative w-72">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Cabecera grupo -->
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo esc_html__('GC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo esc_html__('Grupo Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-xs text-gray-400"><?php echo esc_html__('Barrio Centro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </div>
                        <!-- Estadisticas del grupo -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="text-sm font-bold text-green-600">32</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Pedidos mensuales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="text-sm font-bold text-green-600">4</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?php echo esc_html__('Ahorro medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="text-sm font-bold text-green-600">25%</span>
                            </div>
                        </div>
                        <!-- Productores -->
                        <div class="mt-4">
                            <p class="text-xs text-gray-500 mb-2"><?php echo esc_html__('Productores asociados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold"><?php echo esc_html__('FN', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold"><?php echo esc_html__('QL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="w-8 h-8 bg-gradient-to-br from-teal-400 to-teal-500 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-bold"><?php echo esc_html__('OA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="w-8 h-8 bg-gray-200 rounded-full border-2 border-white flex items-center justify-center text-gray-500 text-xs font-bold">+5</div>
                            </div>
                        </div>
                    </div>
                    <!-- Efectos decorativos -->
                    <div class="absolute -top-3 -right-3 w-20 h-20 bg-green-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-3 -left-3 w-24 h-24 bg-emerald-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_unirse); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_unirse); ?>
                </p>

                <ul class="space-y-3 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <?php foreach ($beneficios_unirse as $beneficio_texto) : ?>
                        <li class="flex items-center gap-3 text-gray-600">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm"><?php echo esc_html($beneficio_texto); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?php echo esc_url($url_unirse_grupo); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold text-lg hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <?php echo esc_html__('Unirme a un Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url($url_crear_grupo); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-green-300 text-green-600 font-semibold text-lg hover:bg-green-50 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php echo esc_html__('Crear un Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
