<?php
/**
 * Partial: Calendario - Agenda
 * Agenda/day view grouped by date.
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

// Group events by day
$eventos_agrupados_por_dia = [];
foreach ( $lista_eventos as $evento ) {
    $fecha_evento = $evento['fecha'] ?? '';
    if ( $fecha_evento ) {
        $clave_dia = date( 'Y-m-d', strtotime( $fecha_evento ) );
        $eventos_agrupados_por_dia[ $clave_dia ][] = $evento;
    }
}
?>

<section class="py-16 px-4 bg-white">
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

        <div class="space-y-8">
            <?php foreach ( $eventos_agrupados_por_dia as $clave_dia => $eventos_del_dia ) :
                $marca_tiempo_dia      = strtotime( $clave_dia );
                $fecha_dia_formateada  = date_i18n( 'l, j \d\e F \d\e Y', $marca_tiempo_dia );
                $es_hoy                = ( date( 'Y-m-d', $marca_tiempo_dia ) === date( 'Y-m-d', current_time( 'timestamp' ) ) );
            ?>
                <div>
                    <!-- Day header -->
                    <div class="flex items-center gap-3 mb-4">
                        <h3 class="font-bold text-lg <?php echo $es_hoy ? '' : 'text-gray-900'; ?> capitalize"
                            <?php if ( $es_hoy ) : ?>
                                style="color: <?php echo esc_attr( $color_principal ); ?>;"
                            <?php endif; ?>
                        >
                            <?php echo esc_html( $fecha_dia_formateada ); ?>
                        </h3>
                        <?php if ( $es_hoy ) : ?>
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full text-white"
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                            >
                                <?php echo esc_html__( 'Hoy', 'flavor-chat-ia' ); ?>
                            </span>
                        <?php endif; ?>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>

                    <!-- Events for this day -->
                    <div class="space-y-3 pl-4 border-l-2" style="border-color: <?php echo esc_attr( $color_principal ); ?>;">
                        <?php foreach ( $eventos_del_dia as $evento ) :
                            $titulo_evento      = $evento['titulo'] ?? '';
                            $fecha_evento       = $evento['fecha'] ?? '';
                            $descripcion_evento = $evento['descripcion'] ?? '';
                            $lugar_evento       = $evento['lugar'] ?? '';
                            $hora_evento        = strtotime( $fecha_evento ) ? date_i18n( 'H:i', strtotime( $fecha_evento ) ) : '';
                        ?>
                            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <?php if ( $titulo_evento ) : ?>
                                            <h4 class="font-semibold text-gray-900">
                                                <?php echo esc_html( $titulo_evento ); ?>
                                            </h4>
                                        <?php endif; ?>
                                        <?php if ( $descripcion_evento ) : ?>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <?php echo esc_html( $descripcion_evento ); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ( $lugar_evento ) : ?>
                                            <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                </svg>
                                                <?php echo esc_html( $lugar_evento ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ( $hora_evento ) : ?>
                                        <span class="text-sm font-medium shrink-0" style="color: <?php echo esc_attr( $color_principal ); ?>;">
                                            <?php echo esc_html( $hora_evento ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
