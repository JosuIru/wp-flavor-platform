<?php
/**
 * Partial: Hero Split Derecha
 *
 * Two columns: image on the left, text content on the right.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $imagen_lateral_url, $color_primario,
 *   $texto_boton, $url_boton, $texto_boton_secundario,
 *   $url_boton_secundario
 */
?>

<section class="w-full bg-white overflow-hidden">

    <div class="max-w-7xl mx-auto px-6 py-16 lg:py-24">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Columna de imagen (izquierda) -->
            <div class="order-1">
                <?php if ( ! empty( $imagen_lateral_url ) ) : ?>
                    <img
                        src="<?php echo esc_url( $imagen_lateral_url ); ?>"
                        alt="<?php echo esc_attr( $titulo ); ?>"
                        class="w-full h-auto rounded-2xl shadow-xl object-cover"
                        loading="eager"
                    />
                <?php else : ?>
                    <div class="w-full aspect-[4/3] rounded-2xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna de texto (derecha) -->
            <div class="order-2">

                <?php if ( ! empty( $titulo ) ) : ?>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-6">
                        <?php echo esc_html( $titulo ); ?>
                    </h1>
                <?php endif; ?>

                <?php if ( ! empty( $subtitulo ) ) : ?>
                    <p class="text-lg sm:text-xl text-gray-600 mb-8 leading-relaxed">
                        <?php echo esc_html( $subtitulo ); ?>
                    </p>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row gap-4">

                    <?php if ( ! empty( $texto_boton ) && ! empty( $url_boton ) ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton ); ?>"
                            class="inline-block px-8 py-4 rounded-lg text-white font-semibold text-base transition-all duration-300 hover:opacity-90 hover:shadow-lg text-center"
                            style="background-color: <?php echo esc_attr( $color_primario ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( ! empty( $texto_boton_secundario ) && ! empty( $url_boton_secundario ) ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton_secundario ); ?>"
                            class="inline-block px-8 py-4 rounded-lg font-semibold text-base transition-all duration-300 border-2 hover:shadow-md text-center"
                            style="color: <?php echo esc_attr( $color_primario ); ?>; border-color: <?php echo esc_attr( $color_primario ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton_secundario ); ?>
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>
