<?php
/**
 * Module Colors Manager
 *
 * Sistema centralizado para gestionar colores de dashboards de módulos.
 * Permite que cada módulo herede los colores del tema activo o defina
 * su propia paleta personalizada.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar colores de módulos
 */
class Flavor_Module_Colors {

    /**
     * Instancia singleton
     *
     * @var Flavor_Module_Colors|null
     */
    private static $instance = null;

    /**
     * Cache de colores por módulo
     *
     * @var array
     */
    private $colors_cache = [];

    /**
     * Colores base disponibles
     *
     * @var array
     */
    private $base_colors = [
        'primary',
        'primary-hover',
        'primary-light',
        'primary-dark',
        'secondary',
        'success',
        'warning',
        'error',
        'info',
    ];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Module_Colors
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_module_components_css' ], 15 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_module_components_css' ], 15 );
    }

    /**
     * Encolar CSS de componentes de módulos
     */
    public function enqueue_module_components_css() {
        wp_enqueue_style(
            'flavor-dashboard-module-components',
            FLAVOR_CHAT_IA_URL . 'assets/css/layouts/dashboard-module-components.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Registrar paleta de colores para un módulo
     *
     * Permite que un módulo defina sus propios colores que sobrescriben
     * los del tema global.
     *
     * @param string $module_id ID del módulo (ej: 'colectivos', 'eventos').
     * @param array  $colors    Array de colores ['primary' => '#hex', ...].
     * @return bool
     */
    public static function register( $module_id, $colors ) {
        $sanitized_colors = [];

        foreach ( $colors as $key => $value ) {
            if ( preg_match( '/^#[a-fA-F0-9]{3,6}$/', $value ) || preg_match( '/^(rgb|hsl)/', $value ) ) {
                $sanitized_colors[ sanitize_key( $key ) ] = $value;
            }
        }

        if ( empty( $sanitized_colors ) ) {
            return false;
        }

        $all_module_colors = get_option( 'flavor_module_colors', [] );
        $all_module_colors[ $module_id ] = $sanitized_colors;
        update_option( 'flavor_module_colors', $all_module_colors );

        // Limpiar cache
        $instance = self::get_instance();
        unset( $instance->colors_cache[ $module_id ] );

        return true;
    }

    /**
     * Obtener color específico para un módulo
     *
     * Prioridad:
     * 1. Color personalizado del módulo (si está registrado)
     * 2. Variable CSS del tema activo
     * 3. Fallback por defecto
     *
     * @param string $module_id ID del módulo.
     * @param string $key       Clave del color (primary, success, etc.).
     * @param string $default   Valor por defecto si no se encuentra.
     * @return string Color en formato hex o CSS variable.
     */
    public static function get( $module_id, $key, $default = null ) {
        $instance = self::get_instance();

        // Verificar cache
        $cache_key = "{$module_id}_{$key}";
        if ( isset( $instance->colors_cache[ $cache_key ] ) ) {
            return $instance->colors_cache[ $cache_key ];
        }

        // Buscar color personalizado del módulo
        $module_colors = get_option( 'flavor_module_colors', [] );
        if ( isset( $module_colors[ $module_id ][ $key ] ) ) {
            $instance->colors_cache[ $cache_key ] = $module_colors[ $module_id ][ $key ];
            return $module_colors[ $module_id ][ $key ];
        }

        // Buscar en el tema activo
        if ( class_exists( 'Flavor_Theme_Manager' ) ) {
            $theme_manager = Flavor_Theme_Manager::get_instance();
            $active_theme = $theme_manager->get_active_theme();

            if ( $active_theme && isset( $active_theme['variables'] ) ) {
                $var_name = "--flavor-{$key}";
                if ( isset( $active_theme['variables'][ $var_name ] ) ) {
                    $instance->colors_cache[ $cache_key ] = $active_theme['variables'][ $var_name ];
                    return $active_theme['variables'][ $var_name ];
                }
            }
        }

        // Fallbacks por defecto
        $defaults = [
            'primary'       => '#3b82f6',
            'primary-hover' => '#2563eb',
            'primary-light' => '#dbeafe',
            'primary-dark'  => '#1d4ed8',
            'secondary'     => '#6b7280',
            'success'       => '#22c55e',
            'warning'       => '#f59e0b',
            'error'         => '#ef4444',
            'info'          => '#3b82f6',
        ];

        $color = $default ?? $defaults[ $key ] ?? '#3b82f6';
        $instance->colors_cache[ $cache_key ] = $color;

        return $color;
    }

    /**
     * Obtener todos los colores de un módulo
     *
     * @param string $module_id ID del módulo.
     * @return array Array completo de colores.
     */
    public static function get_all( $module_id ) {
        $colors = [];

        $instance = self::get_instance();
        foreach ( $instance->base_colors as $key ) {
            $colors[ $key ] = self::get( $module_id, $key );
        }

        return $colors;
    }

    /**
     * Verificar si un módulo tiene colores personalizados
     *
     * @param string $module_id ID del módulo.
     * @return bool
     */
    public static function has_custom_colors( $module_id ) {
        $module_colors = get_option( 'flavor_module_colors', [] );
        return isset( $module_colors[ $module_id ] ) && ! empty( $module_colors[ $module_id ] );
    }

    /**
     * Eliminar colores personalizados de un módulo
     *
     * El módulo volverá a usar los colores del tema global.
     *
     * @param string $module_id ID del módulo.
     * @return bool
     */
    public static function reset( $module_id ) {
        $module_colors = get_option( 'flavor_module_colors', [] );

        if ( isset( $module_colors[ $module_id ] ) ) {
            unset( $module_colors[ $module_id ] );
            update_option( 'flavor_module_colors', $module_colors );

            // Limpiar cache
            $instance = self::get_instance();
            foreach ( $instance->base_colors as $key ) {
                unset( $instance->colors_cache[ "{$module_id}_{$key}" ] );
            }

            return true;
        }

        return false;
    }

    /**
     * Generar CSS variables para un módulo específico
     *
     * Útil para inyectar en el <head> o inline styles.
     *
     * @param string $module_id ID del módulo.
     * @param string $prefix    Prefijo CSS (por defecto: --{module_id}).
     * @return string CSS con variables.
     */
    public static function render_css_vars( $module_id, $prefix = null ) {
        $prefix = $prefix ?? "--{$module_id}";
        $colors = self::get_all( $module_id );

        $css = ":root {\n";
        foreach ( $colors as $key => $value ) {
            $css .= "    {$prefix}-{$key}: {$value};\n";
        }
        $css .= "}\n";

        return $css;
    }

    /**
     * Generar atributo style inline para un color
     *
     * Helper para facilitar la migración de dashboards existentes.
     *
     * @param string $module_id ID del módulo.
     * @param string $property  Propiedad CSS (color, background-color, border-color).
     * @param string $key       Clave del color (primary, success, etc.).
     * @return string Atributo style completo.
     */
    public static function inline_style( $module_id, $property, $key ) {
        $color = self::get( $module_id, $key );
        return sprintf( '%s: %s;', esc_attr( $property ), esc_attr( $color ) );
    }

    /**
     * Obtener lista de módulos con colores personalizados
     *
     * @return array Array de module_ids.
     */
    public static function get_customized_modules() {
        $module_colors = get_option( 'flavor_module_colors', [] );
        return array_keys( $module_colors );
    }

    /**
     * Migrar colores inline a variables CSS
     *
     * Helper para identificar colores hardcodeados en templates.
     *
     * @param string $html HTML con inline styles.
     * @return array Colores encontrados y sugerencias de migración.
     */
    public static function analyze_inline_colors( $html ) {
        $found_colors = [];

        // Buscar colores hex en style attributes
        preg_match_all( '/style=["\'][^"\']*(?:color|background|border)[^:]*:\s*(#[a-fA-F0-9]{3,6})/i', $html, $matches );

        if ( ! empty( $matches[1] ) ) {
            $found_colors = array_unique( $matches[1] );
        }

        // Mapear a variables sugeridas
        $color_map = [
            '#2271b1' => '--dm-primary (WordPress blue)',
            '#3b82f6' => '--dm-primary (Default blue)',
            '#00a32a' => '--dm-success (Green)',
            '#22c55e' => '--dm-success (Green)',
            '#10b981' => '--dm-success (Green)',
            '#f59e0b' => '--dm-warning (Orange)',
            '#dba617' => '--dm-warning (Yellow)',
            '#d63638' => '--dm-error (Red)',
            '#ef4444' => '--dm-error (Red)',
            '#1d2327' => '--dm-text (Dark text)',
            '#646970' => '--dm-text-secondary (Gray text)',
            '#64748b' => '--dm-text-secondary (Gray text)',
        ];

        $suggestions = [];
        foreach ( $found_colors as $color ) {
            $lower_color = strtolower( $color );
            $suggestions[ $color ] = $color_map[ $lower_color ] ?? 'Custom color - consider adding to theme';
        }

        return $suggestions;
    }
}

// Inicializar
Flavor_Module_Colors::get_instance();

/**
 * Helper functions para uso global
 */

/**
 * Obtener color de módulo
 *
 * @param string $module_id ID del módulo.
 * @param string $key       Clave del color.
 * @param string $default   Valor por defecto.
 * @return string
 */
function flavor_module_color( $module_id, $key, $default = null ) {
    return Flavor_Module_Colors::get( $module_id, $key, $default );
}

/**
 * Generar style inline para color de módulo
 *
 * @param string $module_id ID del módulo.
 * @param string $property  Propiedad CSS.
 * @param string $key       Clave del color.
 * @return string
 */
function flavor_module_style( $module_id, $property, $key ) {
    return Flavor_Module_Colors::inline_style( $module_id, $property, $key );
}
