<?php
/**
 * Template: CTA Propuesta Ciudadana
 *
 * Seccion de llamada a la accion para enviar propuestas.
 * Fondo con gradiente, estadistica destacada y boton principal.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;
?>

<section class="flavor-component flavor-section relative py-20 overflow-hidden">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 bg-gradient-to-br from-amber-500 to-orange-600"></div>
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-20 -left-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono -->
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white bg-opacity-20 rounded-full mb-8">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </div>

            <!-- Titulo -->
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                <?php echo esc_html($titulo ?? 'Tu voz importa'); ?>
            </h2>

            <!-- Texto -->
            <p class="text-lg md:text-xl text-white text-opacity-90 mb-8 max-w-2xl mx-auto leading-relaxed">
                <?php echo esc_html($texto ?? 'Las decisiones de tu comunidad se construyen con la participacion de todos. Cada propuesta cuenta, cada voto suma. Se parte del cambio que quieres ver.'); ?>
            </p>

            <!-- Boton CTA -->
            <div class="mb-10">
                <a href="<?php echo esc_url($boton_url ?? '#enviar-propuesta'); ?>"
                   class="inline-flex items-center bg-white text-amber-600 px-10 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Enviar mi Propuesta'); ?>
                </a>
            </div>

            <!-- Estadistica destacada -->
            <div class="inline-flex items-center bg-white bg-opacity-15 backdrop-blur-sm rounded-full px-6 py-3">
                <svg class="w-5 h-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-white font-medium">
                    <?php echo esc_html($estadistica_texto ?? 'Mas de 500 propuestas ciudadanas aprobadas'); ?>
                </span>
            </div>

            <!-- Beneficios secundarios -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="flex items-center justify-center text-white text-opacity-90">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo esc_html__('Proceso transparente', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flex items-center justify-center text-white text-opacity-90">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo esc_html__('Seguimiento en tiempo real', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flex items-center justify-center text-white text-opacity-90">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo esc_html__('Respuesta garantizada', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
