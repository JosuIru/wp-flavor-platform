<?php
/**
 * Trait con helpers compartidos para temas y colores de apps
 *
 * Usado por Flavor_App_Config_API y Flavor_App_Manifest_API
 * para evitar duplicación de código.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait para helpers de temas y colores
 */
trait Flavor_App_Theme_Helpers {

    /**
     * Tema claro por defecto
     *
     * @return array
     */
    protected function get_default_theme() {
        return array(
            'primary'           => '#6366f1',
            'primary_variant'   => '#4f46e5',
            'secondary'         => '#10b981',
            'secondary_variant' => '#059669',
            'background'        => '#ffffff',
            'surface'           => '#f8fafc',
            'error'             => '#ef4444',
            'on_primary'        => '#ffffff',
            'on_secondary'      => '#ffffff',
            'on_background'     => '#1e293b',
            'on_surface'        => '#334155',
            'on_error'          => '#ffffff',
        );
    }

    /**
     * Tema oscuro por defecto
     *
     * @return array
     */
    protected function get_default_dark_theme() {
        return array(
            'primary'           => '#818cf8',
            'primary_variant'   => '#6366f1',
            'secondary'         => '#34d399',
            'secondary_variant' => '#10b981',
            'background'        => '#0f172a',
            'surface'           => '#1e293b',
            'error'             => '#f87171',
            'on_primary'        => '#0f172a',
            'on_secondary'      => '#0f172a',
            'on_background'     => '#f1f5f9',
            'on_surface'        => '#e2e8f0',
            'on_error'          => '#0f172a',
        );
    }

    /**
     * Sanitizar tema completo
     *
     * @param array $theme Tema a sanitizar.
     * @return array
     */
    protected function sanitize_theme( $theme ) {
        $sanitized = array();
        $allowed_keys = array(
            'primary', 'primary_variant', 'secondary', 'secondary_variant',
            'background', 'surface', 'error',
            'on_primary', 'on_secondary', 'on_background', 'on_surface', 'on_error',
        );

        foreach ( $allowed_keys as $key ) {
            if ( isset( $theme[ $key ] ) ) {
                $sanitized[ $key ] = $this->sanitize_color( $theme[ $key ] );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizar color hexadecimal
     *
     * @param string $color Color a sanitizar.
     * @return string Color sanitizado o #000000 si inválido.
     */
    protected function sanitize_color( $color ) {
        // Formato hex: #RGB o #RRGGBB
        if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
            return strtolower( $color );
        }
        return '#000000';
    }

    /**
     * Convertir color hex a formato Flutter (0xFFRRGGBB)
     *
     * @param string $hex Color hexadecimal.
     * @return string Color en formato Flutter.
     */
    protected function hex_to_flutter( $hex ) {
        $hex = ltrim( $hex, '#' );

        // Expandir formato corto #RGB a #RRGGBB
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '0xFF' . strtoupper( $hex );
    }

    /**
     * Convertir string a camelCase
     *
     * @param string $string String a convertir.
     * @return string
     */
    protected function camel_case( $string ) {
        $string = str_replace( array( '-', '_' ), ' ', $string );
        $string = ucwords( $string );
        $string = str_replace( ' ', '', $string );
        return lcfirst( $string );
    }

    /**
     * Todos los presets de temas disponibles
     *
     * @return array
     */
    protected function get_all_theme_presets_data() {
        static $presets = null;

        if ( null !== $presets ) {
            return $presets;
        }

        $presets = array(
            'modern-blue' => array(
                'name' => 'Azul Moderno',
                'light' => array(
                    'primary' => '#3b82f6',
                    'primary_variant' => '#2563eb',
                    'secondary' => '#06b6d4',
                    'secondary_variant' => '#0891b2',
                    'background' => '#ffffff',
                    'surface' => '#f8fafc',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#0f172a',
                    'on_surface' => '#334155',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#60a5fa',
                    'primary_variant' => '#3b82f6',
                    'secondary' => '#22d3ee',
                    'secondary_variant' => '#06b6d4',
                    'background' => '#0f172a',
                    'surface' => '#1e293b',
                    'error' => '#f87171',
                    'on_primary' => '#0f172a',
                    'on_secondary' => '#0f172a',
                    'on_background' => '#f1f5f9',
                    'on_surface' => '#cbd5e1',
                    'on_error' => '#0f172a',
                ),
            ),
            'emerald-green' => array(
                'name' => 'Verde Esmeralda',
                'light' => array(
                    'primary' => '#10b981',
                    'primary_variant' => '#059669',
                    'secondary' => '#14b8a6',
                    'secondary_variant' => '#0d9488',
                    'background' => '#ffffff',
                    'surface' => '#f0fdf4',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#064e3b',
                    'on_surface' => '#166534',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#34d399',
                    'primary_variant' => '#10b981',
                    'secondary' => '#2dd4bf',
                    'secondary_variant' => '#14b8a6',
                    'background' => '#022c22',
                    'surface' => '#064e3b',
                    'error' => '#f87171',
                    'on_primary' => '#022c22',
                    'on_secondary' => '#022c22',
                    'on_background' => '#d1fae5',
                    'on_surface' => '#a7f3d0',
                    'on_error' => '#022c22',
                ),
            ),
            'purple-violet' => array(
                'name' => 'Violeta',
                'light' => array(
                    'primary' => '#8b5cf6',
                    'primary_variant' => '#7c3aed',
                    'secondary' => '#ec4899',
                    'secondary_variant' => '#db2777',
                    'background' => '#ffffff',
                    'surface' => '#faf5ff',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#4c1d95',
                    'on_surface' => '#6b21a8',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#a78bfa',
                    'primary_variant' => '#8b5cf6',
                    'secondary' => '#f472b6',
                    'secondary_variant' => '#ec4899',
                    'background' => '#1e1b4b',
                    'surface' => '#312e81',
                    'error' => '#f87171',
                    'on_primary' => '#1e1b4b',
                    'on_secondary' => '#1e1b4b',
                    'on_background' => '#ede9fe',
                    'on_surface' => '#ddd6fe',
                    'on_error' => '#1e1b4b',
                ),
            ),
            'warm-orange' => array(
                'name' => 'Naranja Cálido',
                'light' => array(
                    'primary' => '#f97316',
                    'primary_variant' => '#ea580c',
                    'secondary' => '#eab308',
                    'secondary_variant' => '#ca8a04',
                    'background' => '#ffffff',
                    'surface' => '#fffbeb',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#422006',
                    'on_background' => '#7c2d12',
                    'on_surface' => '#92400e',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fb923c',
                    'primary_variant' => '#f97316',
                    'secondary' => '#facc15',
                    'secondary_variant' => '#eab308',
                    'background' => '#431407',
                    'surface' => '#7c2d12',
                    'error' => '#f87171',
                    'on_primary' => '#431407',
                    'on_secondary' => '#422006',
                    'on_background' => '#fed7aa',
                    'on_surface' => '#fdba74',
                    'on_error' => '#431407',
                ),
            ),
            'corporate' => array(
                'name' => 'Corporativo',
                'light' => array(
                    'primary' => '#1e40af',
                    'primary_variant' => '#1e3a8a',
                    'secondary' => '#475569',
                    'secondary_variant' => '#334155',
                    'background' => '#ffffff',
                    'surface' => '#f8fafc',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#0f172a',
                    'on_surface' => '#1e293b',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#3b82f6',
                    'primary_variant' => '#1e40af',
                    'secondary' => '#94a3b8',
                    'secondary_variant' => '#64748b',
                    'background' => '#0f172a',
                    'surface' => '#1e293b',
                    'error' => '#f87171',
                    'on_primary' => '#0f172a',
                    'on_secondary' => '#0f172a',
                    'on_background' => '#f1f5f9',
                    'on_surface' => '#e2e8f0',
                    'on_error' => '#0f172a',
                ),
            ),
            'nature' => array(
                'name' => 'Naturaleza',
                'light' => array(
                    'primary' => '#65a30d',
                    'primary_variant' => '#4d7c0f',
                    'secondary' => '#0d9488',
                    'secondary_variant' => '#0f766e',
                    'background' => '#fefce8',
                    'surface' => '#f7fee7',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#365314',
                    'on_surface' => '#3f6212',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#84cc16',
                    'primary_variant' => '#65a30d',
                    'secondary' => '#14b8a6',
                    'secondary_variant' => '#0d9488',
                    'background' => '#1a2e05',
                    'surface' => '#365314',
                    'error' => '#f87171',
                    'on_primary' => '#1a2e05',
                    'on_secondary' => '#1a2e05',
                    'on_background' => '#ecfccb',
                    'on_surface' => '#d9f99d',
                    'on_error' => '#1a2e05',
                ),
            ),
            'minimal' => array(
                'name' => 'Minimalista',
                'light' => array(
                    'primary' => '#18181b',
                    'primary_variant' => '#09090b',
                    'secondary' => '#71717a',
                    'secondary_variant' => '#52525b',
                    'background' => '#ffffff',
                    'surface' => '#fafafa',
                    'error' => '#dc2626',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#ffffff',
                    'on_background' => '#18181b',
                    'on_surface' => '#3f3f46',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fafafa',
                    'primary_variant' => '#e4e4e7',
                    'secondary' => '#a1a1aa',
                    'secondary_variant' => '#71717a',
                    'background' => '#09090b',
                    'surface' => '#18181b',
                    'error' => '#f87171',
                    'on_primary' => '#09090b',
                    'on_secondary' => '#09090b',
                    'on_background' => '#fafafa',
                    'on_surface' => '#d4d4d8',
                    'on_error' => '#09090b',
                ),
            ),
            'rose' => array(
                'name' => 'Rosa',
                'light' => array(
                    'primary' => '#e11d48',
                    'primary_variant' => '#be123c',
                    'secondary' => '#f472b6',
                    'secondary_variant' => '#ec4899',
                    'background' => '#ffffff',
                    'surface' => '#fff1f2',
                    'error' => '#ef4444',
                    'on_primary' => '#ffffff',
                    'on_secondary' => '#4c0519',
                    'on_background' => '#881337',
                    'on_surface' => '#9f1239',
                    'on_error' => '#ffffff',
                ),
                'dark' => array(
                    'primary' => '#fb7185',
                    'primary_variant' => '#f43f5e',
                    'secondary' => '#f9a8d4',
                    'secondary_variant' => '#f472b6',
                    'background' => '#4c0519',
                    'surface' => '#881337',
                    'error' => '#f87171',
                    'on_primary' => '#4c0519',
                    'on_secondary' => '#4c0519',
                    'on_background' => '#ffe4e6',
                    'on_surface' => '#fecdd3',
                    'on_error' => '#4c0519',
                ),
            ),
        );

        return $presets;
    }
}
