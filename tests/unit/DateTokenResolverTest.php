<?php
/**
 * Tests de Flavor_VBP_Date_Token_Resolver.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-visual-builder-pro/collections/class-date-token-resolver.php';

/**
 * Mock de wp_timezone si no está definida en el bootstrap.
 */
if ( ! function_exists( 'wp_timezone' ) ) {
    function wp_timezone() {
        return new DateTimeZone( 'UTC' );
    }
}

class DateTokenResolverTest extends Flavor_TestCase {

    public function test_non_token_values_are_passed_through_unchanged() {
        $this->assertSame( '2026-04-18', Flavor_VBP_Date_Token_Resolver::resolve( '2026-04-18' ) );
        $this->assertSame( '', Flavor_VBP_Date_Token_Resolver::resolve( '' ) );
        $this->assertSame( 'texto-no-fecha', Flavor_VBP_Date_Token_Resolver::resolve( 'texto-no-fecha' ) );
    }

    public function test_today_resolves_to_current_date() {
        $hoy_esperado = ( new DateTime( 'today', wp_timezone() ) )->format( 'Y-m-d' );
        $this->assertSame( $hoy_esperado, Flavor_VBP_Date_Token_Resolver::resolve( '@today' ) );
    }

    public function test_today_plus_days_advances() {
        $hoy     = new DateTime( 'today', wp_timezone() );
        $mas_siete = (clone $hoy)->modify( '+7 day' )->format( 'Y-m-d' );

        $this->assertSame( $mas_siete, Flavor_VBP_Date_Token_Resolver::resolve( '@today+7d' ) );
    }

    public function test_today_minus_days_goes_back() {
        $hoy     = new DateTime( 'today', wp_timezone() );
        $menos_tres = (clone $hoy)->modify( '-3 day' )->format( 'Y-m-d' );

        $this->assertSame( $menos_tres, Flavor_VBP_Date_Token_Resolver::resolve( '@today-3d' ) );
    }

    public function test_today_with_weeks_offset() {
        $hoy        = new DateTime( 'today', wp_timezone() );
        $mas_dos_w  = (clone $hoy)->modify( '+2 week' )->format( 'Y-m-d' );

        $this->assertSame( $mas_dos_w, Flavor_VBP_Date_Token_Resolver::resolve( '@today+2w' ) );
    }

    public function test_today_with_months_offset() {
        $hoy         = new DateTime( 'today', wp_timezone() );
        $mas_un_mes  = (clone $hoy)->modify( '+1 month' )->format( 'Y-m-d' );

        $this->assertSame( $mas_un_mes, Flavor_VBP_Date_Token_Resolver::resolve( '@today+1m' ) );
    }

    public function test_start_and_end_of_month_are_first_and_last_day() {
        $hoy = new DateTime( 'today', wp_timezone() );

        $primer_dia_mes  = (clone $hoy)->modify( 'first day of this month' )->format( 'Y-m-d' );
        $ultimo_dia_mes  = (clone $hoy)->modify( 'last day of this month' )->format( 'Y-m-d' );

        $this->assertSame( $primer_dia_mes, Flavor_VBP_Date_Token_Resolver::resolve( '@start_of_month' ) );
        $this->assertSame( $ultimo_dia_mes, Flavor_VBP_Date_Token_Resolver::resolve( '@end_of_month' ) );
    }

    public function test_start_and_end_of_week_are_monday_and_sunday() {
        $lunes_esperado   = ( new DateTime( 'monday this week', wp_timezone() ) )->format( 'Y-m-d' );
        $domingo_esperado = ( new DateTime( 'sunday this week', wp_timezone() ) )->format( 'Y-m-d' );

        $this->assertSame( $lunes_esperado, Flavor_VBP_Date_Token_Resolver::resolve( '@start_of_week' ) );
        $this->assertSame( $domingo_esperado, Flavor_VBP_Date_Token_Resolver::resolve( '@end_of_week' ) );
    }

    public function test_start_and_end_of_year_cover_january_to_december() {
        $primer_dia_year = ( new DateTime( 'first day of january this year', wp_timezone() ) )->format( 'Y-m-d' );
        $ultimo_dia_year = ( new DateTime( 'last day of december this year', wp_timezone() ) )->format( 'Y-m-d' );

        $this->assertSame( $primer_dia_year, Flavor_VBP_Date_Token_Resolver::resolve( '@start_of_year' ) );
        $this->assertSame( $ultimo_dia_year, Flavor_VBP_Date_Token_Resolver::resolve( '@end_of_year' ) );
    }

    public function test_unknown_tokens_return_empty_string() {
        $this->assertSame( '', Flavor_VBP_Date_Token_Resolver::resolve( '@yesterday' ) );
        $this->assertSame( '', Flavor_VBP_Date_Token_Resolver::resolve( '@today+xd' ) );
        $this->assertSame( '', Flavor_VBP_Date_Token_Resolver::resolve( '@today+' ) );
        $this->assertSame( '', Flavor_VBP_Date_Token_Resolver::resolve( '@totally_invented' ) );
    }

    public function test_integer_offsets_accept_multiple_digits() {
        $hoy       = new DateTime( 'today', wp_timezone() );
        $mas_30    = (clone $hoy)->modify( '+30 day' )->format( 'Y-m-d' );
        $menos_365 = (clone $hoy)->modify( '-365 day' )->format( 'Y-m-d' );

        $this->assertSame( $mas_30, Flavor_VBP_Date_Token_Resolver::resolve( '@today+30d' ) );
        $this->assertSame( $menos_365, Flavor_VBP_Date_Token_Resolver::resolve( '@today-365d' ) );
    }
}
