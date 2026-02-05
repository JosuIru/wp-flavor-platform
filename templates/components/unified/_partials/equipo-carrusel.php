<?php
/**
 * Partial: Equipo - Carrusel
 * Horizontal scrollable team carousel.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Miembros: nombre, cargo, foto, bio, redes
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_miembros    = $items ?? [];
$identificador_carrusel = 'equipo-carrusel-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-12">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>
                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="relative">
            <!-- Navigation buttons -->
            <button
                type="button"
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center hover:bg-gray-50 transition-colors hidden md:flex"
                onclick="document.getElementById('<?php echo esc_attr( $identificador_carrusel ); ?>').scrollBy({left: -300, behavior: 'smooth'})"
                aria-label="<?php echo esc_attr__( 'Anterior', 'flavor-chat-ia' ); ?>"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button
                type="button"
                class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center hover:bg-gray-50 transition-colors hidden md:flex"
                onclick="document.getElementById('<?php echo esc_attr( $identificador_carrusel ); ?>').scrollBy({left: 300, behavior: 'smooth'})"
                aria-label="<?php echo esc_attr__( 'Siguiente', 'flavor-chat-ia' ); ?>"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Scrollable container -->
            <div
                id="<?php echo esc_attr( $identificador_carrusel ); ?>"
                class="flex gap-6 overflow-x-auto scroll-smooth pb-4 snap-x snap-mandatory"
                style="scrollbar-width: thin; scrollbar-color: <?php echo esc_attr( $color_principal ); ?> #f3f4f6;"
            >
                <?php foreach ( $lista_miembros as $miembro ) :
                    $nombre_miembro = $miembro['nombre'] ?? '';
                    $cargo_miembro  = $miembro['cargo'] ?? '';
                    $foto_miembro   = $miembro['foto'] ?? '';
                    $bio_miembro    = $miembro['bio'] ?? '';
                    $redes_miembro  = $miembro['redes'] ?? [];
                ?>
                    <div class="flex-shrink-0 w-72 snap-start bg-white rounded-2xl shadow-md overflow-hidden">
                        <div class="aspect-[4/3] overflow-hidden bg-gray-100">
                            <?php if ( $foto_miembro ) : ?>
                                <img
                                    src="<?php echo esc_url( $foto_miembro ); ?>"
                                    alt="<?php echo esc_attr( $nombre_miembro ); ?>"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            <?php else : ?>
                                <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                    <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-5">
                            <?php if ( $nombre_miembro ) : ?>
                                <h3 class="font-bold text-gray-900 mb-1">
                                    <?php echo esc_html( $nombre_miembro ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $cargo_miembro ) : ?>
                                <p class="text-sm font-medium mb-2" style="color: <?php echo esc_attr( $color_principal ); ?>;">
                                    <?php echo esc_html( $cargo_miembro ); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ( $bio_miembro ) : ?>
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    <?php echo esc_html( $bio_miembro ); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ( ! empty( $redes_miembro ) ) : ?>
                                <div class="flex gap-2 mt-3">
                                    <?php foreach ( $redes_miembro as $nombre_red => $url_red ) : ?>
                                        <a
                                            href="<?php echo esc_url( $url_red ); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-gray-400 hover:opacity-80 transition-opacity text-xs font-semibold uppercase"
                                            style="--hover-color: <?php echo esc_attr( $color_principal ); ?>;"
                                            onmouseover="this.style.color='<?php echo esc_attr( $color_principal ); ?>'"
                                            onmouseout="this.style.color=''"
                                            aria-label="<?php echo esc_attr( $nombre_red ); ?>"
                                        >
                                            <?php echo esc_html( $nombre_red ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
