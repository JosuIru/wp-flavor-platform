<?php
/**
 * Partial: Hero con Buscador
 *
 * Centered hero with a prominent search bar below the title.
 *
 * Variables disponibles (definidas por el dispatcher hero.php):
 *   $titulo, $subtitulo, $imagen_fondo_url, $color_primario,
 *   $mostrar_buscador, $placeholder_buscador, $overlay_oscuro
 */
?>

<section class="relative w-full min-h-[60vh] flex items-center justify-center overflow-hidden">

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

    <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 lg:py-28 text-center">

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

        <?php if ( $mostrar_buscador ) : ?>
            <form class="w-full max-w-2xl mx-auto" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
                <div class="relative flex items-center bg-white rounded-xl shadow-xl overflow-hidden">

                    <span class="pl-5 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>

                    <input
                        type="search"
                        name="s"
                        placeholder="<?php echo esc_attr( $placeholder_buscador ); ?>"
                        class="w-full py-4 px-4 text-gray-700 text-base sm:text-lg focus:outline-none placeholder-gray-400"
                    />

                    <button
                        type="submit"
                        class="flex-shrink-0 px-6 sm:px-8 py-4 text-white font-semibold text-base transition-colors duration-300 hover:opacity-90"
                        style="background-color: <?php echo esc_attr( $color_primario ); ?>;"
                    >
                        <?php esc_html_e( 'Buscar', 'flavor-chat-ia' ); ?>
                    </button>

                </div>
            </form>
        <?php endif; ?>

    </div>

</section>
