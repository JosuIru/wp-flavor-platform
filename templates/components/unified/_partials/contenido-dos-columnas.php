<?php
/**
 * Partial: Contenido - Dos Columnas
 * Two-column text layout.
 *
 * Variables esperadas:
 *   $titulo          (string) Titulo de la seccion
 *   $subtitulo       (string) Subtitulo de la seccion
 *   $contenido       (string) Contenido HTML del bloque
 *   $color_primario  (string) Color primario en formato hex
 *   $imagen_url      (string) URL de la imagen
 *   $posicion_imagen (string) Posicion de la imagen
 *   $url_video       (string) URL del video
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$texto_contenido   = $contenido ?? '';
$color_principal   = $color_primario ?? '#3B82F6';

// Split content roughly in half at a paragraph boundary
$parrafos_contenido     = preg_split( '/(<\/p>|<br\s*\/?>|\n\n)/', $texto_contenido, -1, PREG_SPLIT_DELIM_CAPTURE );
$total_partes           = count( $parrafos_contenido );
$mitad_partes           = intval( ceil( $total_partes / 2 ) );
$contenido_columna_uno  = implode( '', array_slice( $parrafos_contenido, 0, $mitad_partes ) );
$contenido_columna_dos  = implode( '', array_slice( $parrafos_contenido, $mitad_partes ) );
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="mb-10">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>
                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg text-gray-500">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>
                <div class="w-20 h-1 mt-4 rounded" style="background-color: <?php echo esc_attr( $color_principal ); ?>;"></div>
            </div>
        <?php endif; ?>

        <?php if ( $texto_contenido ) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="prose max-w-none text-gray-600 leading-relaxed">
                    <?php echo wp_kses_post( $contenido_columna_uno ); ?>
                </div>
                <div class="prose max-w-none text-gray-600 leading-relaxed">
                    <?php echo wp_kses_post( $contenido_columna_dos ); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
