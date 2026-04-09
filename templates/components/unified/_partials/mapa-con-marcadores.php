<?php
/**
 * Partial: Mapa - Con Marcadores
 * Map with multiple location markers listed below.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $direccion      (string) Direccion principal
 *   $latitud        (float)  Latitud central
 *   $longitud       (float)  Longitud central
 *   $zoom           (int)    Nivel de zoom
 *   $marcadores     (array)  Lista de marcadores: titulo, direccion, latitud, longitud
 *   $altura         (string) Altura del mapa
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$direccion_mapa    = $direccion ?? '';
$latitud_mapa      = $latitud ?? 40.4168;
$longitud_mapa     = $longitud ?? -3.7038;
$nivel_zoom        = $zoom ?? 13;
$lista_marcadores  = $marcadores ?? [];
$altura_mapa       = $altura ?? '400px';

$desplazamiento_bbox = 0.02 / max( 1, $nivel_zoom / 10 );
$parametros_bbox = sprintf(
    'bbox=%s,%s,%s,%s&marker=%s,%s',
    $longitud_mapa - $desplazamiento_bbox,
    $latitud_mapa - $desplazamiento_bbox,
    $longitud_mapa + $desplazamiento_bbox,
    $latitud_mapa + $desplazamiento_bbox,
    $latitud_mapa,
    $longitud_mapa
);
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

        <!-- Map -->
        <div class="rounded-2xl overflow-hidden shadow-md border border-gray-200 mb-8">
            <iframe
                src="<?php echo esc_url( $url_iframe_mapa ); ?>"
                style="width: 100%; height: <?php echo esc_attr( $altura_mapa ); ?>; border: 0;"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="<?php echo esc_attr__( 'Mapa de ubicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
            ></iframe>
        </div>

        <!-- Markers list -->
        <?php if ( ! empty( $lista_marcadores ) ) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ( $lista_marcadores as $indice_marcador => $marcador ) :
                    $titulo_marcador    = $marcador['titulo'] ?? '';
                    $direccion_marcador = $marcador['direccion'] ?? '';
                ?>
                    <div class="flex items-start gap-4 p-4 rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0"
                            style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                        >
                            <?php echo esc_html( $indice_marcador + 1 ); ?>
                        </div>
                        <div>
                            <?php if ( $titulo_marcador ) : ?>
                                <h4 class="font-semibold text-gray-900 mb-1">
                                    <?php echo esc_html( $titulo_marcador ); ?>
                                </h4>
                            <?php endif; ?>
                            <?php if ( $direccion_marcador ) : ?>
                                <p class="text-sm text-gray-600">
                                    <?php echo esc_html( $direccion_marcador ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
