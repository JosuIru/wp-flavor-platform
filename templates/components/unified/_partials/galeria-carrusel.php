<?php
/**
 * Partial: Galeria - Carrusel
 * Horizontal scrollable gallery.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Imagenes: imagen, titulo, descripcion
 *   $columnas       (int)    Numero de columnas
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_imagenes    = $items ?? [];
$identificador_galeria = 'galeria-carrusel-' . wp_unique_id();
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
            <!-- Navigation -->
            <button
                type="button"
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center hover:bg-gray-50 transition-colors hidden md:flex"
                onclick="document.getElementById('<?php echo esc_attr( $identificador_galeria ); ?>').scrollBy({left: -350, behavior: 'smooth'})"
                aria-label="<?php echo esc_attr__( 'Anterior', 'flavor-chat-ia' ); ?>"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button
                type="button"
                class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center hover:bg-gray-50 transition-colors hidden md:flex"
                onclick="document.getElementById('<?php echo esc_attr( $identificador_galeria ); ?>').scrollBy({left: 350, behavior: 'smooth'})"
                aria-label="<?php echo esc_attr__( 'Siguiente', 'flavor-chat-ia' ); ?>"
            >
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Scrollable gallery -->
            <div
                id="<?php echo esc_attr( $identificador_galeria ); ?>"
                class="flex gap-4 overflow-x-auto scroll-smooth pb-4 snap-x snap-mandatory"
                style="scrollbar-width: thin;"
            >
                <?php foreach ( $lista_imagenes as $imagen ) :
                    $url_imagen         = $imagen['imagen'] ?? '';
                    $titulo_imagen      = $imagen['titulo'] ?? '';
                    $descripcion_imagen = $imagen['descripcion'] ?? '';
                ?>
                    <div class="flex-shrink-0 w-80 snap-start group">
                        <div class="rounded-xl overflow-hidden shadow-md bg-white">
                            <?php if ( $url_imagen ) : ?>
                                <img
                                    src="<?php echo esc_url( $url_imagen ); ?>"
                                    alt="<?php echo esc_attr( $titulo_imagen ); ?>"
                                    class="w-full h-56 object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />
                            <?php else : ?>
                                <div class="w-full h-56 bg-gray-200 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            <?php endif; ?>

                            <?php if ( $titulo_imagen || $descripcion_imagen ) : ?>
                                <div class="p-4">
                                    <?php if ( $titulo_imagen ) : ?>
                                        <h3 class="font-semibold text-gray-900 text-sm mb-1">
                                            <?php echo esc_html( $titulo_imagen ); ?>
                                        </h3>
                                    <?php endif; ?>
                                    <?php if ( $descripcion_imagen ) : ?>
                                        <p class="text-xs text-gray-600">
                                            <?php echo esc_html( $descripcion_imagen ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
