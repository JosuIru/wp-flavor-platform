<?php
/**
 * Resuelve tokens relativos de fecha en argumentos de Collections.
 *
 * Los bloques dinámicos a menudo quieren "próximos 7 días" o "último mes"
 * sin hardcodear fechas absolutas que envejezcan. Los sources aceptan
 * fechas en formato Y-m-d, así que introducimos tokens que el registry
 * traduce antes de sanitizar:
 *
 *   @today               → fecha de hoy
 *   @today+7d / @today-3d → offset en días
 *   @today+2w / @today-1w → offset en semanas (7 días)
 *   @today+1m / @today-1m → offset en meses (primer día del mes offset)
 *   @start_of_week       → primer día de la semana actual (lunes)
 *   @end_of_week         → último día (domingo)
 *   @start_of_month      → primer día del mes actual
 *   @end_of_month        → último día del mes actual
 *   @start_of_year       → primer día del año
 *   @end_of_year         → último día del año
 *
 * Todos respetan la zona horaria configurada en WordPress (wp_timezone()).
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro\Collections
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_VBP_Date_Token_Resolver {

    /**
     * Si el valor empieza por '@', lo resuelve como token relativo. Si no,
     * lo devuelve tal cual para que el resto de la pipeline de validación
     * lo trate como fecha absoluta.
     *
     * @param string $valor Posible token (ej: '@today+7d') o fecha literal.
     * @return string Fecha en formato Y-m-d, o string vacío si el token
     *                no se reconoce.
     */
    public static function resolve( $valor ) {
        if ( ! is_string( $valor ) || $valor === '' || $valor[0] !== '@' ) {
            return $valor;
        }

        $tokens_fijos = array(
            '@today'           => 'today',
            '@start_of_week'   => 'monday this week',
            '@end_of_week'     => 'sunday this week',
            '@start_of_month'  => 'first day of this month',
            '@end_of_month'    => 'last day of this month',
            '@start_of_year'   => 'first day of january this year',
            '@end_of_year'     => 'last day of december this year',
        );

        if ( isset( $tokens_fijos[ $valor ] ) ) {
            return self::format_date_in_wp_timezone( $tokens_fijos[ $valor ] );
        }

        // Tokens con offset: @today±N(d|w|m)
        if ( preg_match( '/^@today([+-])(\d+)([dwm])$/', $valor, $captura ) === 1 ) {
            $signo_offset  = $captura[1];
            $cantidad_raw  = (int) $captura[2];
            $unidad_tiempo = $captura[3];

            $mapa_unidades = array(
                'd' => 'day',
                'w' => 'week',
                'm' => 'month',
            );
            $unidad_relative = $mapa_unidades[ $unidad_tiempo ];
            $expresion       = sprintf( '%s%d %s', $signo_offset, $cantidad_raw, $unidad_relative );

            return self::format_date_in_wp_timezone( $expresion );
        }

        return '';
    }

    /**
     * Convierte una expresión de strtotime a Y-m-d respetando la zona
     * horaria de WordPress.
     *
     * @param string $expresion_relativa
     * @return string
     */
    private static function format_date_in_wp_timezone( $expresion_relativa ) {
        try {
            $zona_wp = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
            $datetime = new DateTime( $expresion_relativa, $zona_wp );
            return $datetime->format( 'Y-m-d' );
        } catch ( Exception $excepcion ) {
            return '';
        }
    }
}
