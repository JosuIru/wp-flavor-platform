<?php
/**
 * Partial: Hero Minimalista
 *
 * Clean, minimal hero with just title, subtitle and optional button
 * on a white/light background. No images, no overlays.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $color_primario, $texto_boton, $url_boton,
 *   $subtexto_inferior
 */
?>

<section class="w-full bg-white">

    <div class="max-w-3xl mx-auto px-6 py-20 lg:py-32 text-center">

        <?php if ( ! empty( $titulo ) ) : ?>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-6 tracking-tight">
                <?php echo esc_html( $titulo ); ?>
            </h1>
        <?php endif; ?>

        <?php if ( ! empty( $subtitulo ) ) : ?>
            <p class="text-lg sm:text-xl text-gray-500 mb-10 max-w-xl mx-auto leading-relaxed">
                <?php echo esc_html( $subtitulo ); ?>
            </p>
        <?php endif; ?>

        <?php if ( ! empty( $texto_boton ) && ! empty( $url_boton ) ) : ?>
            <a
                href="<?php echo esc_url( $url_boton ); ?>"
                class="inline-block px-8 py-3.5 rounded-lg text-white font-semibold text-base transition-all duration-300 hover:opacity-90 hover:shadow-md"
                style="background-color: <?php echo esc_attr( $color_primario ); ?>;"
            >
                <?php echo esc_html( $texto_boton ); ?>
            </a>
        <?php endif; ?>

        <?php if ( ! empty( $subtexto_inferior ) ) : ?>
            <p class="mt-8 text-sm text-gray-400">
                <?php echo esc_html( $subtexto_inferior ); ?>
            </p>
        <?php endif; ?>

    </div>

</section>
