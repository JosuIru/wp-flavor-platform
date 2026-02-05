<?php
/**
 * Partial: Hero con Video
 *
 * Hero with embedded video (YouTube/Vimeo iframe) alongside text content.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $color_primario, $texto_boton, $url_boton,
 *   $texto_boton_secundario, $url_boton_secundario, $url_video
 */
?>

<section class="w-full bg-gray-50 overflow-hidden">

    <div class="max-w-7xl mx-auto px-6 py-16 lg:py-24">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Columna de texto -->
            <div>

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

            <!-- Columna de video -->
            <div>
                <?php if ( ! empty( $url_video ) ) : ?>
                    <div class="relative w-full rounded-2xl overflow-hidden shadow-xl bg-black" style="padding-bottom: 56.25%;">
                        <iframe
                            src="<?php echo esc_url( $url_video ); ?>"
                            class="absolute inset-0 w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                            title="<?php echo esc_attr( $titulo ); ?>"
                        ></iframe>
                    </div>
                <?php else : ?>
                    <div class="relative w-full rounded-2xl overflow-hidden bg-gray-200 flex items-center justify-center" style="padding-bottom: 56.25%;">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

</section>
