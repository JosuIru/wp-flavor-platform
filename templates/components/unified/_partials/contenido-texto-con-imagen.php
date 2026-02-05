<?php
/**
 * Partial: Contenido - Texto Con Imagen
 * Text with image on side.
 *
 * Variables esperadas:
 *   $titulo          (string) Titulo de la seccion
 *   $subtitulo       (string) Subtitulo de la seccion
 *   $contenido       (string) Contenido HTML del bloque
 *   $color_primario  (string) Color primario en formato hex
 *   $imagen_url      (string) URL de la imagen
 *   $posicion_imagen (string) Posicion de la imagen: 'izquierda' o 'derecha'
 *   $url_video       (string) URL del video
 */

$titulo_seccion       = $titulo ?? '';
$subtitulo_seccion    = $subtitulo ?? '';
$texto_contenido      = $contenido ?? '';
$color_principal      = $color_primario ?? '#3B82F6';
$url_imagen_contenido = $imagen_url ?? '';
$lado_imagen          = $posicion_imagen ?? 'derecha';
$es_imagen_izquierda  = ( $lado_imagen === 'izquierda' );
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center <?php echo $es_imagen_izquierda ? '' : ''; ?>">
            <!-- Image -->
            <div class="<?php echo $es_imagen_izquierda ? 'order-1' : 'order-1 lg:order-2'; ?>">
                <?php if ( $url_imagen_contenido ) : ?>
                    <div class="rounded-2xl overflow-hidden shadow-lg">
                        <img
                            src="<?php echo esc_url( $url_imagen_contenido ); ?>"
                            alt="<?php echo esc_attr( $titulo_seccion ); ?>"
                            class="w-full h-auto object-cover"
                            loading="lazy"
                        />
                    </div>
                <?php else : ?>
                    <div class="rounded-2xl bg-gray-100 aspect-[4/3] flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Text content -->
            <div class="<?php echo $es_imagen_izquierda ? 'order-2' : 'order-2 lg:order-1'; ?>">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg mb-6" style="color: <?php echo esc_attr( $color_principal ); ?>;">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( $texto_contenido ) : ?>
                    <div class="prose max-w-none text-gray-600 leading-relaxed">
                        <?php echo wp_kses_post( $texto_contenido ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
