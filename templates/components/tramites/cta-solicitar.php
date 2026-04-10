<?php
/**
 * Template: CTA Solicitar Tramite
 *
 * Seccion de llamada a la accion para iniciar tramites.
 * Fondo con gradiente, lista de beneficios y boton principal.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;
?>

<section class="flavor-component flavor-section relative py-20 overflow-hidden">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-amber-500"></div>
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-20 -right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-5xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <!-- Contenido izquierdo -->
                <div class="flex-1 text-center md:text-left">
                    <!-- Titulo -->
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                        <?php echo esc_html($titulo ?? 'Empieza tu tramite ahora'); ?>
                    </h2>

                    <!-- Texto -->
                    <p class="text-lg text-white text-opacity-90 mb-8 leading-relaxed">
                        <?php echo esc_html($texto ?? 'Olvida las colas y los horarios. Gestiona tus tramites municipales desde cualquier dispositivo, en cualquier momento.'); ?>
                    </p>

                    <!-- Lista de beneficios -->
                    <div class="space-y-4 mb-8">
                        <div class="flex items-center text-white">
                            <div class="flex-shrink-0 w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-lg"><?php echo esc_html__('Sin colas ni esperas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center text-white">
                            <div class="flex-shrink-0 w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-lg"><?php echo esc_html__('Disponible 24 horas, 7 dias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flex items-center text-white">
                            <div class="flex-shrink-0 w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-lg"><?php echo esc_html__('Seguimiento en tiempo real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta CTA derecha -->
                <div class="flex-shrink-0 w-full md:w-auto">
                    <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl p-8 text-center max-w-sm mx-auto">
                        <!-- Icono -->
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>

                        <p class="text-white text-opacity-80 mb-6">
                            <?php echo esc_html__('Mas de 150 tramites disponibles para gestionar online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>

                        <!-- Boton principal -->
                        <a href="<?php echo esc_url($boton_url ?? '#iniciar-tramite'); ?>"
                           class="inline-flex items-center bg-white text-orange-600 px-8 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300 w-full justify-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            <?php echo esc_html($boton_texto ?? 'Iniciar Tramite'); ?>
                        </a>

                        <p class="mt-4 text-sm text-white text-opacity-70">
                            <?php echo esc_html__('Necesitaras tu DNI electronico o Cl@ve', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
