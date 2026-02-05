<?php
/**
 * Partial: Contenido - Texto Simple
 * Simple text block with title.
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
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-3xl mx-auto">
        <?php if ( $titulo_seccion ) : ?>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html( $titulo_seccion ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( $subtitulo_seccion ) : ?>
            <p class="text-xl text-gray-500 mb-8 font-light" style="border-left: 3px solid <?php echo esc_attr( $color_principal ); ?>; padding-left: 1rem;">
                <?php echo esc_html( $subtitulo_seccion ); ?>
            </p>
        <?php endif; ?>

        <?php if ( $texto_contenido ) : ?>
            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                <?php echo wp_kses_post( $texto_contenido ); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
