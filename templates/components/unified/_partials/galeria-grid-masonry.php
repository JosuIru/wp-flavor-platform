<?php
/**
 * Partial: Galeria - Grid Masonry
 * Masonry grid gallery.
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
$numero_columnas   = $columnas ?? 3;
?>

<section class="py-16 px-4 bg-white">
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

        <div class="columns-1 sm:columns-2 lg:columns-<?php echo intval( $numero_columnas ); ?> gap-4 space-y-4">
            <?php foreach ( $lista_imagenes as $indice_imagen => $imagen ) :
                $url_imagen        = $imagen['imagen'] ?? '';
                $titulo_imagen     = $imagen['titulo'] ?? '';
                $descripcion_imagen = $imagen['descripcion'] ?? '';
                // Vary heights for masonry effect
                $alturas_variadas = [ 'h-48', 'h-64', 'h-56', 'h-72', 'h-52', 'h-60' ];
                $clase_altura     = $alturas_variadas[ $indice_imagen % count( $alturas_variadas ) ];
            ?>
                <div class="break-inside-avoid group relative overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-shadow">
                    <?php if ( $url_imagen ) : ?>
                        <img
                            src="<?php echo esc_url( $url_imagen ); ?>"
                            alt="<?php echo esc_attr( $titulo_imagen ); ?>"
                            class="w-full <?php echo esc_attr( $clase_altura ); ?> object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                    <?php else : ?>
                        <div class="w-full <?php echo esc_attr( $clase_altura ); ?> bg-gray-200 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <?php if ( $titulo_imagen || $descripcion_imagen ) : ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/0 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                            <div class="p-4 text-white">
                                <?php if ( $titulo_imagen ) : ?>
                                    <h3 class="font-semibold text-sm mb-1"><?php echo esc_html( $titulo_imagen ); ?></h3>
                                <?php endif; ?>
                                <?php if ( $descripcion_imagen ) : ?>
                                    <p class="text-xs opacity-80"><?php echo esc_html( $descripcion_imagen ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
