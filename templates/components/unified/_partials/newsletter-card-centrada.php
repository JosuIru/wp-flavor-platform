<?php
/**
 * Partial: Newsletter - Card Centrada
 * Centered card with email form.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $texto_boton    (string) Texto del boton
 *   $placeholder    (string) Placeholder del campo email
 *   $beneficios     (array)  Lista de beneficios
 */

$titulo_seccion     = $titulo ?? '';
$subtitulo_seccion  = $subtitulo ?? '';
$color_principal    = $color_primario ?? '#3B82F6';
$etiqueta_boton     = $texto_boton ?? 'Suscribirse';
$texto_placeholder  = $placeholder ?? 'tu@email.com';
$identificador_form = 'newsletter-card-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-10 text-center border border-gray-100">
            <!-- Icon -->
            <div
                class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.9;"
            >
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
            </div>

            <?php if ( $titulo_seccion ) : ?>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">
                    <?php echo esc_html( $titulo_seccion ); ?>
                </h2>
            <?php endif; ?>

            <?php if ( $subtitulo_seccion ) : ?>
                <p class="text-gray-600 mb-8">
                    <?php echo esc_html( $subtitulo_seccion ); ?>
                </p>
            <?php endif; ?>

            <form id="<?php echo esc_attr( $identificador_form ); ?>" class="space-y-4" method="post">
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="<?php echo esc_attr( $texto_placeholder ); ?>"
                    class="w-full px-5 py-3 rounded-lg border border-gray-300 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2"
                    style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                    onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                    onblur="this.style.borderColor=''"
                />
                <button
                    type="submit"
                    class="w-full py-3 rounded-lg text-white font-semibold transition-opacity hover:opacity-90"
                    style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                >
                    <?php echo esc_html( $etiqueta_boton ); ?>
                </button>
            </form>

            <p class="text-xs text-gray-400 mt-4">
                <?php echo esc_html__( 'No spam. Puedes darte de baja en cualquier momento.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        </div>
    </div>
</section>
