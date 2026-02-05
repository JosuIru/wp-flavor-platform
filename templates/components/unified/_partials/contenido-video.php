<?php
/**
 * Partial: Contenido - Video
 * Video embed with text.
 *
 * Variables esperadas:
 *   $titulo          (string) Titulo de la seccion
 *   $subtitulo       (string) Subtitulo de la seccion
 *   $contenido       (string) Contenido HTML del bloque
 *   $color_primario  (string) Color primario en formato hex
 *   $imagen_url      (string) URL de la imagen
 *   $posicion_imagen (string) Posicion de la imagen
 *   $url_video       (string) URL del video (YouTube, Vimeo, o directo)
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$texto_contenido   = $contenido ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$enlace_video      = $url_video ?? '';

// Convert YouTube/Vimeo URLs to embed format
$url_embed_video = '';
if ( preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/', $enlace_video, $coincidencias_youtube ) ) {
    $url_embed_video = 'https://www.youtube.com/embed/' . $coincidencias_youtube[1];
} elseif ( preg_match( '/vimeo\.com\/(\d+)/', $enlace_video, $coincidencias_vimeo ) ) {
    $url_embed_video = 'https://player.vimeo.com/video/' . $coincidencias_vimeo[1];
} else {
    $url_embed_video = $enlace_video;
}
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-5xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-10">
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

        <!-- Video embed -->
        <?php if ( $url_embed_video ) : ?>
            <div class="relative rounded-2xl overflow-hidden shadow-xl mb-8" style="padding-bottom: 56.25%;">
                <iframe
                    src="<?php echo esc_url( $url_embed_video ); ?>"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                    title="<?php echo esc_attr( $titulo_seccion ); ?>"
                ></iframe>
            </div>
        <?php endif; ?>

        <!-- Text content below video -->
        <?php if ( $texto_contenido ) : ?>
            <div class="max-w-3xl mx-auto prose max-w-none text-gray-600 leading-relaxed text-center">
                <?php echo wp_kses_post( $texto_contenido ); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
