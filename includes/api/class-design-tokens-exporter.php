<?php
/**
 * Design Tokens Exporter
 *
 * Exporta los tokens de diseño en múltiples formatos estándar:
 * - W3C Design Tokens JSON
 * - CSS Custom Properties
 * - JavaScript/TypeScript
 * - Tailwind Config
 *
 * @package FlavorPlatform
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para exportar Design Tokens
 */
class Flavor_Design_Tokens_Exporter {

    /**
     * Instancia singleton
     *
     * @var Flavor_Design_Tokens_Exporter|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Design_Tokens_Exporter
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'wp_ajax_flavor_export_tokens_w3c', array( $this, 'ajax_export_w3c' ) );
        add_action( 'wp_ajax_flavor_export_tokens_css', array( $this, 'ajax_export_css' ) );
        add_action( 'wp_ajax_flavor_export_tokens_js', array( $this, 'ajax_export_js' ) );
        add_action( 'wp_ajax_flavor_export_tokens_tailwind', array( $this, 'ajax_export_tailwind' ) );
    }

    /**
     * Obtiene los settings de diseño actuales
     *
     * @return array
     */
    private function get_design_settings() {
        if ( class_exists( 'Flavor_Design_Settings' ) ) {
            return Flavor_Design_Settings::get_instance()->get_settings();
        }
        return $this->get_default_settings();
    }

    /**
     * Obtiene los settings por defecto
     *
     * @return array
     */
    private function get_default_settings() {
        return array(
            // Colores
            'primary_color'        => '#3b82f6',
            'secondary_color'      => '#8b5cf6',
            'accent_color'         => '#f59e0b',
            'success_color'        => '#10b981',
            'warning_color'        => '#f59e0b',
            'error_color'          => '#ef4444',
            'background_color'     => '#ffffff',
            'text_color'           => '#1f2937',
            'text_muted_color'     => '#6b7280',
            // Tipografía
            'font_family_headings' => 'Inter',
            'font_family_body'     => 'Inter',
            'font_size_base'       => 16,
            'font_size_h1'         => 48,
            'font_size_h2'         => 36,
            'font_size_h3'         => 28,
            'line_height_base'     => 1.5,
            'line_height_headings' => 1.2,
            // Espaciados
            'container_max_width'  => 1280,
            'section_padding_y'    => 80,
            'section_padding_x'    => 20,
            'grid_gap'             => 24,
            'card_padding'         => 24,
            // Botones
            'button_border_radius' => 8,
            'button_padding_y'     => 12,
            'button_padding_x'     => 24,
            'button_font_size'     => 16,
            'button_font_weight'   => 600,
            // Componentes
            'card_border_radius'   => 12,
            'card_shadow'          => 'medium',
            'hero_overlay_opacity' => 0.6,
            'image_border_radius'  => 8,
        );
    }

    /**
     * Exporta tokens en formato W3C Design Tokens JSON
     *
     * @return array
     */
    public function export_w3c() {
        $settings = $this->get_design_settings();

        $tokens = array(
            '$schema' => 'https://design-tokens.org/schema.json',
            'colors'  => array(
                'primary'   => array(
                    '$value' => $settings['primary_color'],
                    '$type'  => 'color',
                ),
                'secondary' => array(
                    '$value' => $settings['secondary_color'],
                    '$type'  => 'color',
                ),
                'accent'    => array(
                    '$value' => $settings['accent_color'],
                    '$type'  => 'color',
                ),
                'success'   => array(
                    '$value' => $settings['success_color'],
                    '$type'  => 'color',
                ),
                'warning'   => array(
                    '$value' => $settings['warning_color'],
                    '$type'  => 'color',
                ),
                'error'     => array(
                    '$value' => $settings['error_color'],
                    '$type'  => 'color',
                ),
                'background' => array(
                    '$value' => $settings['background_color'],
                    '$type'  => 'color',
                ),
                'text'      => array(
                    'default' => array(
                        '$value' => $settings['text_color'],
                        '$type'  => 'color',
                    ),
                    'muted'   => array(
                        '$value' => $settings['text_muted_color'],
                        '$type'  => 'color',
                    ),
                ),
            ),
            'typography' => array(
                'fontFamily' => array(
                    'headings' => array(
                        '$value' => $settings['font_family_headings'] ?: 'Inter',
                        '$type'  => 'fontFamily',
                    ),
                    'body'     => array(
                        '$value' => $settings['font_family_body'] ?: 'Inter',
                        '$type'  => 'fontFamily',
                    ),
                ),
                'fontSize'   => array(
                    'base' => array(
                        '$value' => $settings['font_size_base'] . 'px',
                        '$type'  => 'dimension',
                    ),
                    'h1'   => array(
                        '$value' => $settings['font_size_h1'] . 'px',
                        '$type'  => 'dimension',
                    ),
                    'h2'   => array(
                        '$value' => $settings['font_size_h2'] . 'px',
                        '$type'  => 'dimension',
                    ),
                    'h3'   => array(
                        '$value' => $settings['font_size_h3'] . 'px',
                        '$type'  => 'dimension',
                    ),
                ),
                'lineHeight' => array(
                    'base'     => array(
                        '$value' => (string) $settings['line_height_base'],
                        '$type'  => 'number',
                    ),
                    'headings' => array(
                        '$value' => (string) $settings['line_height_headings'],
                        '$type'  => 'number',
                    ),
                ),
            ),
            'spacing'    => array(
                'containerMax' => array(
                    '$value' => $settings['container_max_width'] . 'px',
                    '$type'  => 'dimension',
                ),
                'sectionY'     => array(
                    '$value' => $settings['section_padding_y'] . 'px',
                    '$type'  => 'dimension',
                ),
                'sectionX'     => array(
                    '$value' => $settings['section_padding_x'] . 'px',
                    '$type'  => 'dimension',
                ),
                'gridGap'      => array(
                    '$value' => $settings['grid_gap'] . 'px',
                    '$type'  => 'dimension',
                ),
                'cardPadding'  => array(
                    '$value' => $settings['card_padding'] . 'px',
                    '$type'  => 'dimension',
                ),
            ),
            'borderRadius' => array(
                'button' => array(
                    '$value' => $settings['button_border_radius'] . 'px',
                    '$type'  => 'dimension',
                ),
                'card'   => array(
                    '$value' => $settings['card_border_radius'] . 'px',
                    '$type'  => 'dimension',
                ),
                'image'  => array(
                    '$value' => $settings['image_border_radius'] . 'px',
                    '$type'  => 'dimension',
                ),
            ),
            'button'       => array(
                'paddingY'   => array(
                    '$value' => $settings['button_padding_y'] . 'px',
                    '$type'  => 'dimension',
                ),
                'paddingX'   => array(
                    '$value' => $settings['button_padding_x'] . 'px',
                    '$type'  => 'dimension',
                ),
                'fontSize'   => array(
                    '$value' => $settings['button_font_size'] . 'px',
                    '$type'  => 'dimension',
                ),
                'fontWeight' => array(
                    '$value' => (string) $settings['button_font_weight'],
                    '$type'  => 'fontWeight',
                ),
            ),
        );

        return $tokens;
    }

    /**
     * Exporta tokens como CSS Custom Properties
     *
     * @return string
     */
    public function export_css() {
        $settings = $this->get_design_settings();

        $css = "/**\n";
        $css .= " * Flavor Design Tokens - CSS Custom Properties\n";
        $css .= " * Generado el: " . current_time( 'Y-m-d H:i:s' ) . "\n";
        $css .= " */\n\n";

        $css .= ":root {\n";

        // Colores
        $css .= "  /* Colors */\n";
        $css .= "  --flavor-primary: {$settings['primary_color']};\n";
        $css .= "  --flavor-secondary: {$settings['secondary_color']};\n";
        $css .= "  --flavor-accent: {$settings['accent_color']};\n";
        $css .= "  --flavor-success: {$settings['success_color']};\n";
        $css .= "  --flavor-warning: {$settings['warning_color']};\n";
        $css .= "  --flavor-error: {$settings['error_color']};\n";
        $css .= "  --flavor-bg: {$settings['background_color']};\n";
        $css .= "  --flavor-text: {$settings['text_color']};\n";
        $css .= "  --flavor-text-muted: {$settings['text_muted_color']};\n\n";

        // Tipografía
        $font_headings = $settings['font_family_headings'] ?: 'Inter';
        $font_body = $settings['font_family_body'] ?: 'Inter';

        $css .= "  /* Typography */\n";
        $css .= "  --flavor-font-headings: \"{$font_headings}\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;\n";
        $css .= "  --flavor-font-body: \"{$font_body}\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;\n";
        $css .= "  --flavor-font-size-base: {$settings['font_size_base']}px;\n";
        $css .= "  --flavor-font-size-h1: {$settings['font_size_h1']}px;\n";
        $css .= "  --flavor-font-size-h2: {$settings['font_size_h2']}px;\n";
        $css .= "  --flavor-font-size-h3: {$settings['font_size_h3']}px;\n";
        $css .= "  --flavor-line-height-base: {$settings['line_height_base']};\n";
        $css .= "  --flavor-line-height-headings: {$settings['line_height_headings']};\n\n";

        // Espaciados
        $css .= "  /* Spacing */\n";
        $css .= "  --flavor-container-max: {$settings['container_max_width']}px;\n";
        $css .= "  --flavor-section-py: {$settings['section_padding_y']}px;\n";
        $css .= "  --flavor-section-px: {$settings['section_padding_x']}px;\n";
        $css .= "  --flavor-grid-gap: {$settings['grid_gap']}px;\n";
        $css .= "  --flavor-card-padding: {$settings['card_padding']}px;\n\n";

        // Botones
        $css .= "  /* Buttons */\n";
        $css .= "  --flavor-button-radius: {$settings['button_border_radius']}px;\n";
        $css .= "  --flavor-button-py: {$settings['button_padding_y']}px;\n";
        $css .= "  --flavor-button-px: {$settings['button_padding_x']}px;\n";
        $css .= "  --flavor-button-font-size: {$settings['button_font_size']}px;\n";
        $css .= "  --flavor-button-weight: {$settings['button_font_weight']};\n\n";

        // Componentes
        $css .= "  /* Components */\n";
        $css .= "  --flavor-card-radius: {$settings['card_border_radius']}px;\n";
        $css .= "  --flavor-image-radius: {$settings['image_border_radius']}px;\n";
        $css .= "  --flavor-hero-overlay: {$settings['hero_overlay_opacity']};\n";

        $css .= "}\n";

        return $css;
    }

    /**
     * Exporta tokens como módulo JavaScript/TypeScript
     *
     * @return string
     */
    public function export_js() {
        $settings = $this->get_design_settings();

        $font_headings = $settings['font_family_headings'] ?: 'Inter';
        $font_body = $settings['font_family_body'] ?: 'Inter';

        $js = "/**\n";
        $js .= " * Flavor Design Tokens - JavaScript/TypeScript\n";
        $js .= " * Generado el: " . current_time( 'Y-m-d H:i:s' ) . "\n";
        $js .= " */\n\n";

        $js .= "export const tokens = {\n";

        // Colores
        $js .= "  colors: {\n";
        $js .= "    primary: '{$settings['primary_color']}',\n";
        $js .= "    secondary: '{$settings['secondary_color']}',\n";
        $js .= "    accent: '{$settings['accent_color']}',\n";
        $js .= "    success: '{$settings['success_color']}',\n";
        $js .= "    warning: '{$settings['warning_color']}',\n";
        $js .= "    error: '{$settings['error_color']}',\n";
        $js .= "    background: '{$settings['background_color']}',\n";
        $js .= "    text: '{$settings['text_color']}',\n";
        $js .= "    textMuted: '{$settings['text_muted_color']}',\n";
        $js .= "  },\n\n";

        // Tipografía
        $js .= "  typography: {\n";
        $js .= "    fontFamily: {\n";
        $js .= "      headings: '{$font_headings}',\n";
        $js .= "      body: '{$font_body}',\n";
        $js .= "    },\n";
        $js .= "    fontSize: {\n";
        $js .= "      base: {$settings['font_size_base']},\n";
        $js .= "      h1: {$settings['font_size_h1']},\n";
        $js .= "      h2: {$settings['font_size_h2']},\n";
        $js .= "      h3: {$settings['font_size_h3']},\n";
        $js .= "    },\n";
        $js .= "    lineHeight: {\n";
        $js .= "      base: {$settings['line_height_base']},\n";
        $js .= "      headings: {$settings['line_height_headings']},\n";
        $js .= "    },\n";
        $js .= "  },\n\n";

        // Espaciados
        $js .= "  spacing: {\n";
        $js .= "    containerMax: {$settings['container_max_width']},\n";
        $js .= "    sectionY: {$settings['section_padding_y']},\n";
        $js .= "    sectionX: {$settings['section_padding_x']},\n";
        $js .= "    gridGap: {$settings['grid_gap']},\n";
        $js .= "    cardPadding: {$settings['card_padding']},\n";
        $js .= "  },\n\n";

        // Border Radius
        $js .= "  borderRadius: {\n";
        $js .= "    button: {$settings['button_border_radius']},\n";
        $js .= "    card: {$settings['card_border_radius']},\n";
        $js .= "    image: {$settings['image_border_radius']},\n";
        $js .= "  },\n\n";

        // Botones
        $js .= "  button: {\n";
        $js .= "    paddingY: {$settings['button_padding_y']},\n";
        $js .= "    paddingX: {$settings['button_padding_x']},\n";
        $js .= "    fontSize: {$settings['button_font_size']},\n";
        $js .= "    fontWeight: {$settings['button_font_weight']},\n";
        $js .= "  },\n";

        $js .= "};\n\n";

        // TypeScript types
        $js .= "// TypeScript Types\n";
        $js .= "export type FlavorTokens = typeof tokens;\n";
        $js .= "export type FlavorColors = keyof typeof tokens.colors;\n";

        return $js;
    }

    /**
     * Exporta tokens como configuración de Tailwind
     *
     * @return string
     */
    public function export_tailwind() {
        $settings = $this->get_design_settings();

        $font_headings = $settings['font_family_headings'] ?: 'Inter';
        $font_body = $settings['font_family_body'] ?: 'Inter';

        $tw = "/**\n";
        $tw .= " * Flavor Design Tokens - Tailwind CSS Config\n";
        $tw .= " * Generado el: " . current_time( 'Y-m-d H:i:s' ) . "\n";
        $tw .= " * \n";
        $tw .= " * Copia este objeto en tu archivo tailwind.config.js dentro de 'theme.extend'\n";
        $tw .= " */\n\n";

        $tw .= "module.exports = {\n";
        $tw .= "  theme: {\n";
        $tw .= "    extend: {\n";

        // Colores
        $tw .= "      colors: {\n";
        $tw .= "        flavor: {\n";
        $tw .= "          primary: '{$settings['primary_color']}',\n";
        $tw .= "          secondary: '{$settings['secondary_color']}',\n";
        $tw .= "          accent: '{$settings['accent_color']}',\n";
        $tw .= "          success: '{$settings['success_color']}',\n";
        $tw .= "          warning: '{$settings['warning_color']}',\n";
        $tw .= "          error: '{$settings['error_color']}',\n";
        $tw .= "          bg: '{$settings['background_color']}',\n";
        $tw .= "          text: '{$settings['text_color']}',\n";
        $tw .= "          'text-muted': '{$settings['text_muted_color']}',\n";
        $tw .= "        },\n";
        $tw .= "      },\n\n";

        // Fuentes
        $tw .= "      fontFamily: {\n";
        $tw .= "        headings: ['{$font_headings}', 'system-ui', 'sans-serif'],\n";
        $tw .= "        body: ['{$font_body}', 'system-ui', 'sans-serif'],\n";
        $tw .= "      },\n\n";

        // Tamaños de fuente
        $tw .= "      fontSize: {\n";
        $tw .= "        'flavor-base': ['{$settings['font_size_base']}px', { lineHeight: '{$settings['line_height_base']}' }],\n";
        $tw .= "        'flavor-h1': ['{$settings['font_size_h1']}px', { lineHeight: '{$settings['line_height_headings']}' }],\n";
        $tw .= "        'flavor-h2': ['{$settings['font_size_h2']}px', { lineHeight: '{$settings['line_height_headings']}' }],\n";
        $tw .= "        'flavor-h3': ['{$settings['font_size_h3']}px', { lineHeight: '{$settings['line_height_headings']}' }],\n";
        $tw .= "      },\n\n";

        // Espaciados
        $tw .= "      spacing: {\n";
        $tw .= "        'flavor-section-y': '{$settings['section_padding_y']}px',\n";
        $tw .= "        'flavor-section-x': '{$settings['section_padding_x']}px',\n";
        $tw .= "        'flavor-grid-gap': '{$settings['grid_gap']}px',\n";
        $tw .= "        'flavor-card': '{$settings['card_padding']}px',\n";
        $tw .= "      },\n\n";

        // Max Width
        $tw .= "      maxWidth: {\n";
        $tw .= "        'flavor-container': '{$settings['container_max_width']}px',\n";
        $tw .= "      },\n\n";

        // Border Radius
        $tw .= "      borderRadius: {\n";
        $tw .= "        'flavor-btn': '{$settings['button_border_radius']}px',\n";
        $tw .= "        'flavor-card': '{$settings['card_border_radius']}px',\n";
        $tw .= "        'flavor-img': '{$settings['image_border_radius']}px',\n";
        $tw .= "      },\n";

        $tw .= "    },\n";
        $tw .= "  },\n";
        $tw .= "};\n";

        return $tw;
    }

    /**
     * AJAX: Exportar tokens W3C
     */
    public function ajax_export_w3c() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $tokens = $this->export_w3c();

        wp_send_json_success( array(
            'content'  => wp_json_encode( $tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
            'filename' => 'flavor-design-tokens.json',
            'mime'     => 'application/json',
        ) );
    }

    /**
     * AJAX: Exportar tokens CSS
     */
    public function ajax_export_css() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $css = $this->export_css();

        wp_send_json_success( array(
            'content'  => $css,
            'filename' => 'flavor-design-tokens.css',
            'mime'     => 'text/css',
        ) );
    }

    /**
     * AJAX: Exportar tokens JS
     */
    public function ajax_export_js() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $js = $this->export_js();

        wp_send_json_success( array(
            'content'  => $js,
            'filename' => 'flavor-design-tokens.ts',
            'mime'     => 'text/typescript',
        ) );
    }

    /**
     * AJAX: Exportar tokens Tailwind
     */
    public function ajax_export_tailwind() {
        check_ajax_referer( 'flavor_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN ) ) );
        }

        $tailwind = $this->export_tailwind();

        wp_send_json_success( array(
            'content'  => $tailwind,
            'filename' => 'tailwind.config.flavor.js',
            'mime'     => 'text/javascript',
        ) );
    }

    /**
     * Obtiene todos los formatos de exportación disponibles
     *
     * @return array
     */
    public function get_export_formats() {
        return array(
            'w3c'      => array(
                'id'          => 'w3c',
                'name'        => __( 'W3C Design Tokens (JSON)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Formato estándar compatible con Figma Tokens y otras herramientas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'extension'   => 'json',
                'action'      => 'flavor_export_tokens_w3c',
            ),
            'css'      => array(
                'id'          => 'css',
                'name'        => __( 'CSS Custom Properties', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Variables CSS nativas para usar directamente en tu proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'extension'   => 'css',
                'action'      => 'flavor_export_tokens_css',
            ),
            'js'       => array(
                'id'          => 'js',
                'name'        => __( 'JavaScript/TypeScript', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Módulo ES6 con tipado TypeScript incluido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'extension'   => 'ts',
                'action'      => 'flavor_export_tokens_js',
            ),
            'tailwind' => array(
                'id'          => 'tailwind',
                'name'        => __( 'Tailwind CSS Config', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'description' => __( 'Configuración para integrar en tu archivo tailwind.config.js', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'extension'   => 'js',
                'action'      => 'flavor_export_tokens_tailwind',
            ),
        );
    }
}

// Inicializar
Flavor_Design_Tokens_Exporter::get_instance();
