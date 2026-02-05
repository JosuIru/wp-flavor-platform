<?php
/**
 * Partial: Hero Centrado
 *
 * Full-width hero with centered text, background image, overlay,
 * title, subtitle and CTA button.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $imagen_fondo_url, $color_primario,
 *   $texto_boton, $url_boton, $overlay_oscuro
 */
?>

<section class="relative w-full min-h-[70vh] flex items-center justify-center overflow-hidden">

    <?php if ( ! empty( $imagen_fondo_url ) ) : ?>
        <img
            src="<?php echo esc_url( $imagen_fondo_url ); ?>"
            alt=""
            class="absolute inset-0 w-full h-full object-cover"
            loading="eager"
        />
    <?php endif; ?>

    <?php if ( $overlay_oscuro ) : ?>
        <div class="absolute inset-0 bg-black/50"></div>
    <?php endif; ?>

    <div class="relative z-10 max-w-4xl mx-auto px-6 py-24 text-center">

        <?php if ( ! empty( $titulo ) ) : ?>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 <?php echo $overlay_oscuro || ! empty( $imagen_fondo_url ) ? 'text-white' : 'text-gray-900'; ?>">
                <?php echo esc_html( $titulo ); ?>
            </h1>
        <?php endif; ?>

        <?php if ( ! empty( $subtitulo ) ) : ?>
            <p class="text-lg sm:text-xl lg:text-2xl mb-10 max-w-2xl mx-auto <?php echo $overlay_oscuro || ! empty( $imagen_fondo_url ) ? 'text-gray-200' : 'text-gray-600'; ?>">
                <?php echo esc_html( $subtitulo ); ?>
            </p>
        <?php endif; ?>

        <?php if ( ! empty( $texto_boton ) && ! empty( $url_boton ) ) : ?>
            <a
                href="<?php echo esc_url( $url_boton ); ?>"
                class="inline-block px-8 py-4 rounded-lg text-white font-semibold text-lg transition-all duration-300 hover:opacity-90 hover:shadow-lg transform hover:-translate-y-0.5"
                style="background-color: <?php echo esc_attr( $color_primario ); ?>;"
            >
                <?php echo esc_html( $texto_boton ); ?>
            </a>
        <?php endif; ?>

    </div>

</section>
