<?php
/**
 * Partial: Calendario - Lista de Eventos
 * Chronological event list.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $eventos        (array)  Lista de eventos: titulo, fecha, descripcion, lugar
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_eventos     = $eventos ?? [];

// Sort events by date
usort( $lista_eventos, function( $evento_a, $evento_b ) {
    return strtotime( $evento_a['fecha'] ?? '0' ) - strtotime( $evento_b['fecha'] ?? '0' );
});
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-3xl mx-auto">
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

        <div class="space-y-4">
            <?php foreach ( $lista_eventos as $evento ) :
                $titulo_evento      = $evento['titulo'] ?? '';
                $fecha_evento       = $evento['fecha'] ?? '';
                $descripcion_evento = $evento['descripcion'] ?? '';
                $lugar_evento       = $evento['lugar'] ?? '';

                $marca_tiempo_evento = strtotime( $fecha_evento );
                $dia_formateado      = $marca_tiempo_evento ? date_i18n( 'd', $marca_tiempo_evento ) : '';
                $mes_formateado      = $marca_tiempo_evento ? date_i18n( 'M', $marca_tiempo_evento ) : '';
                $hora_formateada     = $marca_tiempo_evento ? date_i18n( 'H:i', $marca_tiempo_evento ) : '';
            ?>
                <div class="flex gap-4 bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                    <!-- Date badge -->
                    <div class="shrink-0 w-16 text-center">
                        <div
                            class="rounded-lg overflow-hidden"
                            style="border: 2px solid <?php echo esc_attr( $color_principal ); ?>;"
                        >
                            <div class="text-white text-xs font-bold py-1 uppercase" style="background-color: <?php echo esc_attr( $color_principal ); ?>;">
                                <?php echo esc_html( $mes_formateado ); ?>
                            </div>
                            <div class="text-2xl font-bold text-gray-900 py-2">
                                <?php echo esc_html( $dia_formateado ); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Event info -->
                    <div class="flex-1 min-w-0">
                        <?php if ( $titulo_evento ) : ?>
                            <h3 class="font-bold text-gray-900 text-lg mb-1">
                                <?php echo esc_html( $titulo_evento ); ?>
                            </h3>
                        <?php endif; ?>

                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-2">
                            <?php if ( $hora_formateada ) : ?>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo esc_html( $hora_formateada ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( $lugar_evento ) : ?>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo esc_html( $lugar_evento ); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ( $descripcion_evento ) : ?>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                <?php echo esc_html( $descripcion_evento ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
