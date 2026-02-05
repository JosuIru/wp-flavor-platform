<?php
/**
 * Partial: Testimonios - Grid
 * Grid of testimonial cards.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Testimonios: nombre, cargo, texto, foto, valoracion
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_testimonios = $items ?? [];
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ( $lista_testimonios as $testimonio ) :
                $nombre_autor     = $testimonio['nombre'] ?? '';
                $cargo_autor      = $testimonio['cargo'] ?? '';
                $texto_testimonio = $testimonio['texto'] ?? '';
                $foto_autor       = $testimonio['foto'] ?? '';
                $valoracion_autor = intval( $testimonio['valoracion'] ?? 5 );
            ?>
                <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-shadow p-6 flex flex-col border border-gray-100">
                    <!-- Stars -->
                    <?php if ( $valoracion_autor > 0 ) : ?>
                        <div class="flex gap-1 mb-4">
                            <?php for ( $estrella = 1; $estrella <= 5; $estrella++ ) : ?>
                                <svg class="w-4 h-4 <?php echo $estrella <= $valoracion_autor ? '' : 'opacity-30'; ?>"
                                     style="color: <?php echo esc_attr( $color_principal ); ?>;"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Quote -->
                    <?php if ( $texto_testimonio ) : ?>
                        <p class="text-gray-700 leading-relaxed flex-1 mb-6">
                            &ldquo;<?php echo esc_html( $texto_testimonio ); ?>&rdquo;
                        </p>
                    <?php endif; ?>

                    <!-- Author -->
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <?php if ( $foto_autor ) : ?>
                            <img
                                src="<?php echo esc_url( $foto_autor ); ?>"
                                alt="<?php echo esc_attr( $nombre_autor ); ?>"
                                class="w-10 h-10 rounded-full object-cover"
                                loading="lazy"
                            />
                        <?php else : ?>
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                            >
                                <?php echo esc_html( mb_substr( $nombre_autor, 0, 1 ) ); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <?php if ( $nombre_autor ) : ?>
                                <div class="font-semibold text-gray-900 text-sm">
                                    <?php echo esc_html( $nombre_autor ); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $cargo_autor ) : ?>
                                <div class="text-xs text-gray-500">
                                    <?php echo esc_html( $cargo_autor ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
