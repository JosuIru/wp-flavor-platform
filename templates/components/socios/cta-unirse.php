<?php
/**
 * Template: CTA Unirse como Socio
 *
 * Seccion de llamada a la accion con testimonio de socio,
 * garantia de cancelacion y boton principal.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;
?>

<section class="flavor-component flavor-section relative py-20 overflow-hidden">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 bg-gradient-to-br from-rose-500 to-pink-500"></div>
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-20 -left-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Titulo -->
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                <?php echo esc_html($titulo ?? 'No te quedes fuera'); ?>
            </h2>

            <p class="text-lg md:text-xl text-white text-opacity-90 mb-12 max-w-2xl mx-auto leading-relaxed">
                <?php echo esc_html($texto ?? 'Cada dia mas personas se unen a nuestra comunidad. Descubre por que miles de vecinos ya disfrutan de las ventajas de ser socio.'); ?>
            </p>

            <!-- Testimonio -->
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl p-8 max-w-2xl mx-auto mb-12">
                <div class="flex flex-col items-center">
                    <!-- Avatar del testimonio -->
                    <div class="w-16 h-16 bg-white bg-opacity-30 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl font-bold text-white">
                            <?php echo esc_html(mb_substr($testimonio_nombre ?? 'M', 0, 1)); ?>
                        </span>
                    </div>

                    <!-- Cita -->
                    <blockquote class="text-white text-lg italic mb-4 leading-relaxed">
                        &ldquo;<?php echo esc_html($testimonio_texto ?? 'Desde que soy socia he conocido gente increible, he ahorrado en mis compras locales y he participado en eventos que de otra forma no habria descubierto. Merece mucho la pena.'); ?>&rdquo;
                    </blockquote>

                    <!-- Nombre y detalle -->
                    <div>
                        <div class="text-white font-semibold">
                            <?php echo esc_html($testimonio_nombre ?? 'Maria Gonzalez'); ?>
                        </div>
                        <div class="text-white text-opacity-70 text-sm">
                            <?php echo esc_html($testimonio_detalle ?? 'Socia Premium desde 2024'); ?>
                        </div>
                    </div>

                    <!-- Estrellas de valoracion -->
                    <div class="flex items-center mt-3">
                        <?php for ($indice_estrella_cta = 0; $indice_estrella_cta < 5; $indice_estrella_cta++): ?>
                            <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Boton CTA -->
            <div class="mb-8">
                <a href="<?php echo esc_url($boton_url ?? '/socios/unirme/'); ?>"
                   class="inline-flex items-center bg-white text-rose-600 px-10 py-4 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <?php echo esc_html($boton_texto ?? 'Unirme Ahora'); ?>
                </a>
            </div>

            <!-- Garantia -->
            <div class="inline-flex items-center bg-white bg-opacity-15 backdrop-blur-sm rounded-full px-6 py-3">
                <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-white font-medium">
                    <?php echo esc_html($garantia_texto ?? 'Cancela cuando quieras. Sin permanencia ni compromisos.'); ?>
                </span>
            </div>
        </div>
    </div>
</section>
