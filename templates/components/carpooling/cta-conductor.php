<?php
/**
 * Template: CTA Conductor
 *
 * @var string $titulo
 * @var string $texto
 * @var string $boton_texto
 * @var string $boton_url
 * @var string $color_fondo
 * @var string $component_classes
 */

// Defaults
$titulo = $titulo ?? '¿Tienes coche? ¡Comparte tus viajes!';
$texto = $texto ?? 'Registra tus viajes y comparte los gastos con otros viajeros. Es fácil, seguro y bueno para el planeta.';
$boton_texto = $boton_texto ?? 'Publicar Viaje';
$boton_url = $boton_url ?? '#';
$color_fondo = $color_fondo ?? '#1e40af';
$component_classes = $component_classes ?? '';

// Calcular color de texto basado en el color de fondo
$rgb = sscanf($color_fondo, "#%02x%02x%02x");
$brightness = (($rgb[0] * 299) + ($rgb[1] * 587) + ($rgb[2] * 114)) / 1000;
$text_color = ($brightness > 155) ? 'text-gray-900' : 'text-white';
$button_color = ($brightness > 155) ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-100';
$button_text_color = ($brightness > 155) ? 'text-white' : 'text-gray-900';
?>

<section class="py-20 <?php echo esc_attr($component_classes); ?>"
         style="background-color: <?php echo esc_attr($color_fondo); ?>">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                <!-- Contenido -->
                <div class="flex-1 text-center md:text-left">
                    <!-- Icono -->
                    <div class="inline-block mb-6">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 <?php echo esc_attr($text_color); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Título -->
                    <h2 class="text-3xl md:text-4xl font-bold <?php echo esc_attr($text_color); ?> mb-4">
                        <?php echo esc_html($titulo); ?>
                    </h2>

                    <!-- Texto -->
                    <p class="text-lg <?php echo esc_attr($text_color); ?> opacity-90 mb-6 leading-relaxed">
                        <?php echo esc_html($texto); ?>
                    </p>

                    <!-- Características -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="flex items-center <?php echo esc_attr($text_color); ?> opacity-90">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><?php _e('Fácil y rápido', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flex items-center <?php echo esc_attr($text_color); ?> opacity-90">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><?php _e('Sin comisiones', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flex items-center <?php echo esc_attr($text_color); ?> opacity-90">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><?php _e('Pago seguro', 'flavor-chat-ia'); ?></span>
                        </div>
                        <div class="flex items-center <?php echo esc_attr($text_color); ?> opacity-90">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><?php _e('Tú decides el precio', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="flex-shrink-0">
                    <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl p-8 text-center">
                        <div class="mb-6">
                            <div class="text-5xl font-bold <?php echo esc_attr($text_color); ?> mb-2">50€</div>
                            <div class="<?php echo esc_attr($text_color); ?> opacity-75"><?php _e('Ahorro medio mensual', 'flavor-chat-ia'); ?></div>
                        </div>

                        <a href="<?php echo esc_url($boton_url); ?>"
                           class="inline-block <?php echo esc_attr($button_color); ?> <?php echo esc_attr($button_text_color); ?> px-8 py-4 rounded-lg font-semibold transition duration-300 transform hover:scale-105 shadow-xl">
                            <?php echo esc_html($boton_texto); ?>
                            <svg class="inline-block w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>

                        <p class="mt-4 text-sm <?php echo esc_attr($text_color); ?> opacity-75">
                            <?php _e('Sin compromisos. Cancela cuando quieras.', 'flavor-chat-ia'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
