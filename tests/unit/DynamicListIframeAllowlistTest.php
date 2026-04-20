<?php
/**
 * Tests del filtro de iframes en plantillas custom de Lista Dinámica.
 *
 * La lógica real vive en Flavor_VBP_Canvas::filter_iframes_by_host_allowlist
 * (privada); se replica aquí el algoritmo para lockearlo. Cubrir subdominios,
 * hosts con casing distinto y iframes sin src.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class DynamicListIframeAllowlistTest extends Flavor_TestCase {

    /**
     * Replica de filter_iframes_by_host_allowlist (sin dependencia de WP).
     */
    private function filter_iframes( $html_sanitizado, array $hosts_permitidos ) {
        return preg_replace_callback(
            '/<iframe\b([^>]*)(\/?>)(?:[^<]*<\/iframe>)?/i',
            function ( $coincidencia ) use ( $hosts_permitidos ) {
                $atributos_iframe = $coincidencia[1];

                if ( ! preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $atributos_iframe, $captura_src ) ) {
                    return '';
                }

                $url_src = $captura_src[1];
                $partes  = parse_url( $url_src );
                $host    = isset( $partes['host'] ) ? strtolower( $partes['host'] ) : '';

                if ( $host === '' ) {
                    return '';
                }

                foreach ( $hosts_permitidos as $host_permitido ) {
                    $host_permitido = strtolower( trim( $host_permitido ) );
                    if ( $host_permitido === '' ) {
                        continue;
                    }
                    if ( $host === $host_permitido || substr( $host, -1 * ( strlen( $host_permitido ) + 1 ) ) === '.' . $host_permitido ) {
                        return $coincidencia[0];
                    }
                }

                return '';
            },
            $html_sanitizado
        );
    }

    public function test_preserves_iframe_with_exact_host_match() {
        $html = '<iframe src="https://youtube.com/embed/abc" width="560"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringContainsString( 'youtube.com/embed/abc', $resultado );
        $this->assertStringContainsString( '<iframe', $resultado );
    }

    public function test_preserves_iframe_with_subdomain_of_allowed_host() {
        $html = '<iframe src="https://www.youtube.com/embed/abc"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringContainsString( 'www.youtube.com', $resultado );
    }

    public function test_strips_iframe_with_disallowed_host() {
        $html = '<iframe src="https://evil.example.com/"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com', 'vimeo.com' ) );

        $this->assertStringNotContainsString( 'iframe', $resultado );
        $this->assertStringNotContainsString( 'evil.example.com', $resultado );
    }

    public function test_strips_iframe_without_src() {
        $html = '<iframe width="560"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringNotContainsString( 'iframe', $resultado );
    }

    public function test_strips_iframe_with_empty_src() {
        $html = '<iframe src=""></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringNotContainsString( 'iframe', $resultado );
    }

    public function test_host_partial_match_not_treated_as_subdomain() {
        // "evilyoutube.com" no es subdominio de "youtube.com"; el suffix
        // match debe comprobar el '.' literal antes, no solo ends-with.
        $html = '<iframe src="https://evilyoutube.com/malware"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringNotContainsString( 'iframe', $resultado );
    }

    public function test_case_insensitive_host_match() {
        $html = '<iframe src="https://YouTube.COM/embed/xyz"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'YOUTUBE.com' ) );

        $this->assertStringContainsString( 'iframe', $resultado );
    }

    public function test_multiple_allowed_hosts_in_same_template() {
        $html = '<iframe src="https://youtube.com/embed/1"></iframe>'
              . '<iframe src="https://vimeo.com/987"></iframe>'
              . '<iframe src="https://badhost.net/"></iframe>';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com', 'vimeo.com' ) );

        $this->assertStringContainsString( 'youtube.com/embed/1', $resultado );
        $this->assertStringContainsString( 'vimeo.com/987', $resultado );
        $this->assertStringNotContainsString( 'badhost.net', $resultado );
    }

    public function test_empty_allowlist_entry_is_ignored() {
        // Un string vacío en la allowlist no debería hacer match con
        // todo (ends-with '' es true).
        $html = '<iframe src="https://evil.com/"></iframe>';

        $resultado = $this->filter_iframes( $html, array( '', 'youtube.com' ) );

        $this->assertStringNotContainsString( 'iframe', $resultado );
    }

    public function test_iframe_without_closing_tag_is_also_filtered() {
        // <iframe ... /> self-closing style
        $html = '<iframe src="https://evil.com/" />';

        $resultado = $this->filter_iframes( $html, array( 'youtube.com' ) );

        $this->assertStringNotContainsString( 'evil.com', $resultado );
    }
}
