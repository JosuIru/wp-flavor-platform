<?php
/**
 * Partial: Mapa - Con Sidebar
 * Map with sidebar listing locations.
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
$altura_mapa       = $altura ?? '500px';

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

<section class="py-16 px-4 bg-gray-50">
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

        <div class="flex flex-col lg:flex-row gap-6 rounded-2xl overflow-hidden shadow-md border border-gray-200 bg-white">
            <!-- Sidebar -->
            <div class="w-full lg:w-80 shrink-0 overflow-y-auto" style="max-height: <?php echo esc_attr( $altura_mapa ); ?>;">
                <?php if ( ! empty( $lista_marcadores ) ) : ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ( $lista_marcadores as $indice_marcador => $marcador ) :
                            $titulo_marcador    = $marcador['titulo'] ?? '';
                            $direccion_marcador = $marcador['direccion'] ?? '';
                        ?>
                            <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                                    >
                                        <?php echo esc_html( $indice_marcador + 1 ); ?>
                                    </span>
                                    <div>
                                        <?php if ( $titulo_marcador ) : ?>
                                            <h4 class="font-semibold text-gray-900 text-sm">
                                                <?php echo esc_html( $titulo_marcador ); ?>
                                            </h4>
                                        <?php endif; ?>
                                        <?php if ( $direccion_marcador ) : ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo esc_html( $direccion_marcador ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="p-6">
                        <?php if ( $direccion_mapa ) : ?>
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-gray-700">
                                    <?php echo esc_html( $direccion_mapa ); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Map -->
            <div class="flex-1">
                <iframe
                    src="<?php echo esc_url( $url_iframe_mapa ); ?>"
                    style="width: 100%; height: <?php echo esc_attr( $altura_mapa ); ?>; border: 0;"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="<?php echo esc_attr__( 'Mapa de ubicaciones', 'flavor-chat-ia' ); ?>"
                ></iframe>
            </div>
        </div>
    </div>
</section>
