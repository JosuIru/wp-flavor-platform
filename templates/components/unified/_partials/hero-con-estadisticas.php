<?php
/**
 * Partial: Hero con Estadisticas
 *
 * Centered hero with stats counters displayed at the bottom.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $imagen_fondo_url, $color_primario,
 *   $texto_boton, $url_boton, $mostrar_estadisticas,
 *   $estadisticas, $overlay_oscuro
 *
 * $estadisticas es un array de arrays con claves: 'numero', 'etiqueta'
 */
?>

<section class="relative w-full overflow-hidden">

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

    <?php
    $tiene_fondo_visual    = $overlay_oscuro || ! empty( $imagen_fondo_url );
    $clase_color_titulo    = $tiene_fondo_visual ? 'text-white' : 'text-gray-900';
    $clase_color_subtitulo = $tiene_fondo_visual ? 'text-gray-200' : 'text-gray-600';
    ?>

    <div class="relative z-10">

        <!-- Contenido principal -->
        <div class="max-w-4xl mx-auto px-6 pt-20 lg:pt-28 pb-16 text-center">

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

        <!-- Barra de estadisticas -->
        <?php if ( $mostrar_estadisticas && ! empty( $estadisticas ) && is_array( $estadisticas ) ) : ?>
            <div class="border-t <?php echo $tiene_fondo_visual ? 'border-white/20' : 'border-gray-200'; ?>">
                <div class="max-w-6xl mx-auto px-6 py-12">

                    <?php
                    $total_estadisticas      = count( $estadisticas );
                    $clase_grid_estadisticas = 'grid-cols-2 md:grid-cols-' . min( $total_estadisticas, 4 );
                    ?>

                    <div class="grid <?php echo esc_attr( $clase_grid_estadisticas ); ?> gap-8 text-center">

                        <?php foreach ( $estadisticas as $estadistica_item ) : ?>
                            <div class="flex flex-col items-center">
                                <span
                                    class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-2"
                                    style="color: <?php echo esc_attr( $color_primario ); ?>;"
                                >
                                    <?php echo esc_html( $estadistica_item['numero'] ?? '' ); ?>
                                </span>
                                <span class="text-sm sm:text-base <?php echo $tiene_fondo_visual ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    <?php echo esc_html( $estadistica_item['etiqueta'] ?? '' ); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>

</section>
