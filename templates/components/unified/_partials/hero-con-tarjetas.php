<?php
/**
 * Partial: Hero con Tarjetas
 *
 * Hero with floating feature cards overlapping the bottom section.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $imagen_fondo_url, $color_primario,
 *   $texto_boton, $url_boton, $tarjetas, $overlay_oscuro
 *
 * $tarjetas es un array de arrays con claves: 'icono', 'titulo', 'descripcion'
 */
?>

<section class="relative w-full overflow-hidden">

    <!-- Fondo superior del hero -->
    <div class="relative min-h-[50vh] flex items-center justify-center pb-32">

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
        <?php else : ?>
            <div class="absolute inset-0" style="background-color: <?php echo esc_attr( $color_primario ); ?>; opacity: 0.05;"></div>
        <?php endif; ?>

        <?php
        $tiene_fondo_visual    = $overlay_oscuro || ! empty( $imagen_fondo_url );
        $clase_color_titulo    = $tiene_fondo_visual ? 'text-white' : 'text-gray-900';
        $clase_color_subtitulo = $tiene_fondo_visual ? 'text-gray-200' : 'text-gray-600';
        ?>

        <div class="relative z-10 max-w-4xl mx-auto px-6 pt-20 lg:pt-28 text-center">

            <?php if ( ! empty( $titulo ) ) : ?>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 <?php echo esc_attr( $clase_color_titulo ); ?>">
                    <?php echo esc_html( $titulo ); ?>
                </h1>
            <?php endif; ?>

            <?php if ( ! empty( $subtitulo ) ) : ?>
                <p class="text-lg sm:text-xl mb-10 max-w-2xl mx-auto <?php echo esc_attr( $clase_color_subtitulo ); ?>">
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

    </div>

    <!-- Tarjetas flotantes -->
    <?php if ( ! empty( $tarjetas ) && is_array( $tarjetas ) ) : ?>
        <div class="relative z-20 max-w-6xl mx-auto px-6 -mt-24 pb-16">

            <?php
            $total_tarjetas      = count( $tarjetas );
            $clase_grid_tarjetas = 'grid-cols-1 sm:grid-cols-2';
            if ( $total_tarjetas >= 3 ) {
                $clase_grid_tarjetas = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
            }
            if ( $total_tarjetas >= 4 ) {
                $clase_grid_tarjetas = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
            }
            ?>

            <div class="grid <?php echo esc_attr( $clase_grid_tarjetas ); ?> gap-6">

                <?php foreach ( $tarjetas as $tarjeta_item ) : ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">

                        <?php if ( ! empty( $tarjeta_item['icono'] ) ) : ?>
                            <div
                                class="w-12 h-12 rounded-lg flex items-center justify-center mb-4 text-white text-xl"
                                style="background-color: <?php echo esc_attr( $color_primario ); ?>;"
                            >
                                <?php echo esc_html( $tarjeta_item['icono'] ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $tarjeta_item['titulo'] ) ) : ?>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                <?php echo esc_html( $tarjeta_item['titulo'] ); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ( ! empty( $tarjeta_item['descripcion'] ) ) : ?>
                            <p class="text-gray-500 text-sm leading-relaxed">
                                <?php echo esc_html( $tarjeta_item['descripcion'] ); ?>
                            </p>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div>

        </div>
    <?php endif; ?>

</section>
