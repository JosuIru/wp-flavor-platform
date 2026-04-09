<?php
/**
 * Partial: Calendario - Mensual
 * Monthly calendar grid view.
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

// Build calendar for current month
$marca_tiempo_actual = current_time( 'timestamp' );
$mes_actual          = intval( date( 'n', $marca_tiempo_actual ) );
$anio_actual         = intval( date( 'Y', $marca_tiempo_actual ) );
$dia_actual          = intval( date( 'j', $marca_tiempo_actual ) );
$dias_en_mes         = intval( date( 't', $marca_tiempo_actual ) );
$primer_dia_semana   = intval( date( 'w', mktime( 0, 0, 0, $mes_actual, 1, $anio_actual ) ) );

$nombres_dias_semana = [
    __( 'Dom', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Lun', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Mar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Mie', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Jue', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Vie', FLAVOR_PLATFORM_TEXT_DOMAIN ),
    __( 'Sab', FLAVOR_PLATFORM_TEXT_DOMAIN ),
];

$nombre_mes_actual = date_i18n( 'F Y', $marca_tiempo_actual );

// Map events to days
$eventos_por_dia = [];
foreach ( $lista_eventos as $evento ) {
    $fecha_evento = $evento['fecha'] ?? '';
    if ( $fecha_evento ) {
        $dia_evento = intval( date( 'j', strtotime( $fecha_evento ) ) );
        $mes_evento = intval( date( 'n', strtotime( $fecha_evento ) ) );
        $anio_evento = intval( date( 'Y', strtotime( $fecha_evento ) ) );
        if ( $mes_evento === $mes_actual && $anio_evento === $anio_actual ) {
            $eventos_por_dia[ $dia_evento ][] = $evento;
        }
    }
}
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-4xl mx-auto">
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

        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
            <!-- Month header -->
            <div class="px-6 py-4 text-center" style="background-color: <?php echo esc_attr( $color_principal ); ?>;">
                <h3 class="text-xl font-bold text-white capitalize">
                    <?php echo esc_html( $nombre_mes_actual ); ?>
                </h3>
            </div>

            <!-- Day names -->
            <div class="grid grid-cols-7 border-b border-gray-200">
                <?php foreach ( $nombres_dias_semana as $nombre_dia ) : ?>
                    <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <?php echo esc_html( $nombre_dia ); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Calendar grid -->
            <div class="grid grid-cols-7">
                <?php
                // Empty cells before first day
                for ( $celda_vacia = 0; $celda_vacia < $primer_dia_semana; $celda_vacia++ ) : ?>
                    <div class="min-h-[80px] p-2 border-b border-r border-gray-100 bg-gray-50"></div>
                <?php endfor;

                // Day cells
                for ( $numero_dia = 1; $numero_dia <= $dias_en_mes; $numero_dia++ ) :
                    $es_hoy              = ( $numero_dia === $dia_actual );
                    $tiene_eventos       = isset( $eventos_por_dia[ $numero_dia ] );
                    $posicion_en_semana  = ( $primer_dia_semana + $numero_dia - 1 ) % 7;
                ?>
                    <div class="min-h-[80px] p-2 border-b border-r border-gray-100 <?php echo $es_hoy ? 'bg-blue-50' : ''; ?>">
                        <span class="inline-flex items-center justify-center w-7 h-7 text-sm rounded-full <?php echo $es_hoy ? 'text-white font-bold' : 'text-gray-700'; ?>"
                            <?php if ( $es_hoy ) : ?>
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                            <?php endif; ?>
                        >
                            <?php echo esc_html( $numero_dia ); ?>
                        </span>

                        <?php if ( $tiene_eventos ) : ?>
                            <?php foreach ( $eventos_por_dia[ $numero_dia ] as $evento_del_dia ) : ?>
                                <div
                                    class="mt-1 px-1.5 py-0.5 rounded text-xs text-white truncate"
                                    style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.85;"
                                    title="<?php echo esc_attr( $evento_del_dia['titulo'] ?? '' ); ?>"
                                >
                                    <?php echo esc_html( $evento_del_dia['titulo'] ?? '' ); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endfor;

                // Remaining empty cells
                $celdas_restantes = ( 7 - ( ( $primer_dia_semana + $dias_en_mes ) % 7 ) ) % 7;
                for ( $celda_vacia = 0; $celda_vacia < $celdas_restantes; $celda_vacia++ ) : ?>
                    <div class="min-h-[80px] p-2 border-b border-r border-gray-100 bg-gray-50"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>
