<?php
/**
 * Partial: Newsletter - Inline
 * Inline horizontal email + button.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $texto_boton    (string) Texto del boton
 *   $placeholder    (string) Placeholder del campo email
 *   $beneficios     (array)  Lista de beneficios
 */

$titulo_seccion      = $titulo ?? '';
$subtitulo_seccion   = $subtitulo ?? '';
$color_principal     = $color_primario ?? '#3B82F6';
$etiqueta_boton      = $texto_boton ?? 'Suscribirse';
$texto_placeholder   = $placeholder ?? 'tu@email.com';
$identificador_form  = 'newsletter-inline-' . wp_unique_id();
?>

<section class="py-12 px-4" style="background-color: <?php echo esc_attr( $color_principal ); ?>;">
    <div class="max-w-4xl mx-auto text-center">
        <?php if ( $titulo_seccion ) : ?>
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">
                <?php echo esc_html( $titulo_seccion ); ?>
            </h2>
        <?php endif; ?>
        <?php if ( $subtitulo_seccion ) : ?>
            <p class="text-white/80 mb-6 max-w-xl mx-auto">
                <?php echo esc_html( $subtitulo_seccion ); ?>
            </p>
        <?php endif; ?>

        <form id="<?php echo esc_attr( $identificador_form ); ?>" class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto" method="post">
            <input
                type="email"
                name="email"
                required
                placeholder="<?php echo esc_attr( $texto_placeholder ); ?>"
                class="flex-1 px-5 py-3 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white/50 border-0"
            />
            <button
                type="submit"
                class="px-6 py-3 rounded-lg bg-white font-semibold transition-opacity hover:opacity-90 shrink-0"
                style="color: <?php echo esc_attr( $color_principal ); ?>;"
            >
                <?php echo esc_html( $etiqueta_boton ); ?>
            </button>
        </form>
    </div>
</section>
