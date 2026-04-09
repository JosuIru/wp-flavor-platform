<?php
/**
 * Partial: Mapa - Embed Simple
 * Simple Google Maps/OpenStreetMap embed iframe.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $direccion      (string) Direccion para mostrar
 *   $latitud        (float)  Latitud
 *   $longitud       (float)  Longitud
 *   $zoom           (int)    Nivel de zoom
 *   $marcadores     (array)  Marcadores del mapa
 *   $altura         (string) Altura del mapa (ej. '400px')
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$direccion_mapa    = $direccion ?? '';
$latitud_mapa      = $latitud ?? 40.4168;
$longitud_mapa     = $longitud ?? -3.7038;
$nivel_zoom        = $zoom ?? 15;
$altura_mapa       = $altura ?? '400px';

// Construir URL de OpenStreetMap embed
$parametros_bbox = '';
if ( $latitud_mapa && $longitud_mapa ) {
    $desplazamiento_bbox = 0.01 / max( 1, $nivel_zoom / 10 );
    $parametros_bbox = sprintf(
        'bbox=%s,%s,%s,%s&marker=%s,%s',
        $longitud_mapa - $desplazamiento_bbox,
        $latitud_mapa - $desplazamiento_bbox,
        $longitud_mapa + $desplazamiento_bbox,
        $latitud_mapa + $desplazamiento_bbox,
        $latitud_mapa,
        $longitud_mapa
    );
}

$url_iframe_mapa = 'https://www.openstreetmap.org/export/embed.html?' . $parametros_bbox . '&layer=mapnik';
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-8">
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

        <div class="rounded-2xl overflow-hidden shadow-md border border-gray-200">
            <iframe
                src="<?php echo esc_url( $url_iframe_mapa ); ?>"
                style="width: 100%; height: <?php echo esc_attr( $altura_mapa ); ?>; border: 0;"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="<?php echo esc_attr( $direccion_mapa ?: __( 'Mapa de ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>"
            ></iframe>
        </div>

        <?php if ( $direccion_mapa ) : ?>
            <div class="mt-4 text-center">
                <p class="text-gray-600 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo esc_html( $direccion_mapa ); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>
