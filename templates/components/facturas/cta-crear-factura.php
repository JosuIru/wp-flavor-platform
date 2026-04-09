<?php
/**
 * Template: Facturas CTA Crear Factura
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Empieza a facturar profesionalmente';
$descripcion_cta = $descripcion_cta ?? 'Crea facturas con aspecto profesional en minutos. Sin conocimientos contables necesarios.';
$url_primera_factura = $url_primera_factura ?? '/facturas/crear/';
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #F0FDFA 0%, #D1FAE5 100%);">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-12">
            <!-- Mockup de factura -->
            <div class="flex-shrink-0 order-2 lg:order-1">
                <div class="relative w-72">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 border border-gray-100">
                        <!-- Cabecera factura -->
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
                            <div>
                                <div class="w-10 h-10 rounded-lg bg-teal-500 flex items-center justify-center mb-2">
                                    <span class="text-white font-bold text-sm"><?php echo esc_html__('FC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <p class="text-xs text-gray-400"><?php echo esc_html__('Mi Empresa S.L.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold text-teal-600"><?php echo esc_html__('FACTURA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="text-xs text-gray-400">#2024-0042</p>
                            </div>
                        </div>
                        <!-- Lineas de factura -->
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600"><?php echo esc_html__('Servicio de diseno web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-medium text-gray-800"><?php echo esc_html__('1.200&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600"><?php echo esc_html__('Mantenimiento mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-medium text-gray-800"><?php echo esc_html__('300&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600"><?php echo esc_html__('Hosting anual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-medium text-gray-800"><?php echo esc_html__('150&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span><?php echo esc_html__('Subtotal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span><?php echo esc_html__('1.650&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mb-2">
                                <span><?php echo esc_html__('IVA 21%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span><?php echo esc_html__('346,50&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-gray-800">
                                <span><?php echo esc_html__('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="text-teal-600"><?php echo esc_html__('1.996,50&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                        <!-- Estado -->
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                <?php echo esc_html__('Pagada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>
                    <!-- Efectos decorativos -->
                    <div class="absolute -top-3 -right-3 w-20 h-20 bg-teal-400/20 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-3 -left-3 w-24 h-24 bg-emerald-400/20 rounded-full blur-2xl"></div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="flex-1 text-center lg:text-left order-1 lg:order-2">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($titulo_cta); ?>
                </h2>
                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    <?php echo esc_html($descripcion_cta); ?>
                </p>

                <ul class="space-y-3 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <li class="flex items-center gap-3 text-gray-600">
                        <svg class="w-5 h-5 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm"><?php echo esc_html__('Numeracion automatica de facturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-600">
                        <svg class="w-5 h-5 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm"><?php echo esc_html__('Calculo automatico de impuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-600">
                        <svg class="w-5 h-5 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm"><?php echo esc_html__('Historial completo de facturacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </li>
                </ul>

                <a href="<?php echo esc_url($url_primera_factura); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-teal-500 to-emerald-600 text-white font-semibold text-lg hover:from-teal-600 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?php echo esc_html__('Crear mi Primera Factura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>
