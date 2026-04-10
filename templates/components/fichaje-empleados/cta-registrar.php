<?php
/**
 * Template: Fichaje Empleados CTA Registrar
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Moderniza el control horario';
$descripcion_cta = $descripcion_cta ?? 'Digitaliza el registro de jornada de tus empleados con un sistema sencillo, fiable y conforme a la normativa vigente.';
$aviso_normativa = $aviso_normativa ?? 'Cumple con la normativa de registro horario';
$url_activar = $url_activar ?? '/fichaje/';
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);">
    <div class="flavor-container">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Badge -->
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-100 text-slate-700 text-sm font-medium mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo esc_html__('Registro de jornada digital', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>

            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-600 mb-8 leading-relaxed max-w-2xl mx-auto">
                <?php echo esc_html($descripcion_cta); ?>
            </p>

            <!-- Aviso de normativa -->
            <div class="inline-flex items-center gap-3 px-6 py-4 rounded-xl bg-slate-800 text-white mb-10">
                <svg class="w-6 h-6 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="font-medium text-sm"><?php echo esc_html($aviso_normativa); ?></span>
            </div>

            <!-- Ventajas rapidas -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 max-w-3xl mx-auto">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                    <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-gray-700"><?php echo esc_html__('Configuracion en 5 minutos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flex items-center gap-3 p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                    <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-gray-700"><?php echo esc_html__('Sin hardware adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flex items-center gap-3 p-4 rounded-xl bg-white shadow-sm border border-gray-100">
                    <svg class="w-5 h-5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-gray-700"><?php echo esc_html__('Soporte personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Boton CTA -->
            <a href="<?php echo esc_url($url_activar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold text-lg hover:from-slate-700 hover:to-gray-800 transition-all shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?php echo esc_html__('Activar Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
