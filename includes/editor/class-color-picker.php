<?php
/**
 * Color Picker Avanzado
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Color_Picker {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Paletas predefinidas
     */
    private $palettes = [];

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_palettes();
        $this->init_hooks();
    }

    /**
     * Inicializar paletas
     */
    private function init_palettes() {
        $this->palettes = [
            'material' => [
                'name' => 'Material Design',
                'colors' => [
                    '#F44336', '#E91E63', '#9C27B0', '#673AB7',
                    '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
                    '#009688', '#4CAF50', '#8BC34A', '#CDDC39',
                    '#FFEB3B', '#FFC107', '#FF9800', '#FF5722',
                    '#795548', '#9E9E9E', '#607D8B', '#000000',
                ],
            ],
            'tailwind' => [
                'name' => 'Tailwind CSS',
                'colors' => [
                    '#EF4444', '#F97316', '#F59E0B', '#EAB308',
                    '#84CC16', '#22C55E', '#10B981', '#14B8A6',
                    '#06B6D4', '#0EA5E9', '#3B82F6', '#6366F1',
                    '#8B5CF6', '#A855F7', '#D946EF', '#EC4899',
                    '#F43F5E', '#64748B', '#1F2937', '#FFFFFF',
                ],
            ],
            'pastel' => [
                'name' => 'Pasteles',
                'colors' => [
                    '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9',
                    '#BAE1FF', '#E8DAEF', '#FADBD8', '#D5F5E3',
                    '#D6EAF8', '#FCF3CF', '#F5EEF8', '#FDEBD0',
                    '#E8F8F5', '#FEF9E7', '#F9EBEA', '#EBF5FB',
                    '#E9F7EF', '#FDF2E9', '#F4ECF7', '#EAECEE',
                ],
            ],
            'corporate' => [
                'name' => 'Corporativo',
                'colors' => [
                    '#1A365D', '#2C5282', '#2B6CB0', '#3182CE',
                    '#4299E1', '#63B3ED', '#1C4532', '#276749',
                    '#2F855A', '#38A169', '#48BB78', '#68D391',
                    '#744210', '#975A16', '#B7791F', '#D69E2E',
                    '#742A2A', '#9B2C2C', '#C53030', '#E53E3E',
                ],
            ],
            'dark' => [
                'name' => 'Modo Oscuro',
                'colors' => [
                    '#0F172A', '#1E293B', '#334155', '#475569',
                    '#64748B', '#94A3B8', '#CBD5E1', '#E2E8F0',
                    '#18181B', '#27272A', '#3F3F46', '#52525B',
                    '#71717A', '#A1A1AA', '#D4D4D8', '#E4E4E7',
                    '#111827', '#1F2937', '#374151', '#4B5563',
                ],
            ],
            'gradient_starts' => [
                'name' => 'Inicios Gradiente',
                'colors' => [
                    '#667eea', '#f093fb', '#4facfe', '#43e97b',
                    '#fa709a', '#fee140', '#30cfd0', '#a8edea',
                    '#ff9a9e', '#ffecd2', '#d299c2', '#89f7fe',
                    '#c2e9fb', '#fad0c4', '#a1c4fd', '#fbc2eb',
                    '#e0c3fc', '#f5576c', '#4481eb', '#ff0844',
                ],
            ],
        ];

        // Permitir personalización de paletas
        $this->palettes = apply_filters('flavor_color_palettes', $this->palettes);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_flavor_get_color_palettes', [$this, 'ajax_get_palettes']);
        add_action('wp_ajax_flavor_save_custom_colors', [$this, 'ajax_save_custom_colors']);
        add_action('wp_ajax_flavor_get_color_suggestions', [$this, 'ajax_get_color_suggestions']);
    }

    /**
     * Obtener paletas
     */
    public function ajax_get_palettes() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        $custom_colors = get_option('flavor_custom_colors', []);

        wp_send_json_success([
            'palettes' => $this->palettes,
            'custom_colors' => $custom_colors,
            'recent_colors' => $this->get_recent_colors(),
        ]);
    }

    /**
     * Guardar colores personalizados
     */
    public function ajax_save_custom_colors() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $colors = $_POST['colors'] ?? [];

        // Sanitizar colores
        $sanitized_colors = [];
        foreach ($colors as $color) {
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                $sanitized_colors[] = strtoupper($color);
            }
        }

        // Limitar a 20 colores
        $sanitized_colors = array_slice($sanitized_colors, 0, 20);

        update_option('flavor_custom_colors', $sanitized_colors);

        wp_send_json_success(['colors' => $sanitized_colors]);
    }

    /**
     * Obtener colores recientes del usuario
     */
    private function get_recent_colors() {
        $user_id = get_current_user_id();
        $recent = get_user_meta($user_id, 'flavor_recent_colors', true);

        if (!is_array($recent)) {
            return [];
        }

        return array_slice($recent, 0, 10);
    }

    /**
     * Guardar color reciente
     *
     * @param string $color
     */
    public function save_recent_color($color) {
        if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return;
        }

        $user_id = get_current_user_id();
        $recent = get_user_meta($user_id, 'flavor_recent_colors', true);

        if (!is_array($recent)) {
            $recent = [];
        }

        // Quitar si ya existe
        $color = strtoupper($color);
        $recent = array_diff($recent, [$color]);

        // Añadir al principio
        array_unshift($recent, $color);

        // Limitar a 10
        $recent = array_slice($recent, 0, 10);

        update_user_meta($user_id, 'flavor_recent_colors', $recent);
    }

    /**
     * Sugerencias de colores complementarios
     */
    public function ajax_get_color_suggestions() {
        check_ajax_referer('flavor_editor_nonce', 'nonce');

        $base_color = sanitize_text_field($_POST['color'] ?? '#3B82F6');

        $suggestions = $this->generate_color_suggestions($base_color);

        wp_send_json_success($suggestions);
    }

    /**
     * Generar sugerencias de colores
     *
     * @param string $hex
     * @return array
     */
    public function generate_color_suggestions($hex) {
        $rgb = $this->hex_to_rgb($hex);
        $hsl = $this->rgb_to_hsl($rgb);

        return [
            'complementary' => $this->hsl_to_hex([
                ($hsl[0] + 180) % 360,
                $hsl[1],
                $hsl[2],
            ]),
            'analogous' => [
                $this->hsl_to_hex([($hsl[0] + 30) % 360, $hsl[1], $hsl[2]]),
                $this->hsl_to_hex([($hsl[0] - 30 + 360) % 360, $hsl[1], $hsl[2]]),
            ],
            'triadic' => [
                $this->hsl_to_hex([($hsl[0] + 120) % 360, $hsl[1], $hsl[2]]),
                $this->hsl_to_hex([($hsl[0] + 240) % 360, $hsl[1], $hsl[2]]),
            ],
            'split_complementary' => [
                $this->hsl_to_hex([($hsl[0] + 150) % 360, $hsl[1], $hsl[2]]),
                $this->hsl_to_hex([($hsl[0] + 210) % 360, $hsl[1], $hsl[2]]),
            ],
            'shades' => [
                $this->hsl_to_hex([$hsl[0], $hsl[1], max(0, $hsl[2] - 20)]),
                $this->hsl_to_hex([$hsl[0], $hsl[1], max(0, $hsl[2] - 10)]),
                $this->hsl_to_hex([$hsl[0], $hsl[1], min(100, $hsl[2] + 10)]),
                $this->hsl_to_hex([$hsl[0], $hsl[1], min(100, $hsl[2] + 20)]),
            ],
        ];
    }

    /**
     * Convertir HEX a RGB
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convertir RGB a HSL
     */
    private function rgb_to_hsl($rgb) {
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        return [
            round($h * 360),
            round($s * 100),
            round($l * 100),
        ];
    }

    /**
     * Convertir HSL a HEX
     */
    private function hsl_to_hex($hsl) {
        $h = $hsl[0] / 360;
        $s = $hsl[1] / 100;
        $l = $hsl[2] / 100;

        if ($s === 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue_to_rgb($p, $q, $h + 1/3);
            $g = $this->hue_to_rgb($p, $q, $h);
            $b = $this->hue_to_rgb($p, $q, $h - 1/3);
        }

        return sprintf(
            '#%02X%02X%02X',
            round($r * 255),
            round($g * 255),
            round($b * 255)
        );
    }

    /**
     * Helper para conversión HSL
     */
    private function hue_to_rgb($p, $q, $t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    /**
     * Obtener paletas
     */
    public function get_palettes() {
        return $this->palettes;
    }

    /**
     * Generar gradiente
     *
     * @param string $color1
     * @param string $color2
     * @param string $direction
     * @return string
     */
    public function generate_gradient($color1, $color2, $direction = 'to right') {
        return "linear-gradient({$direction}, {$color1}, {$color2})";
    }

    /**
     * Generar CSS de variables de color
     *
     * @param array $colors
     * @return string
     */
    public function generate_css_variables($colors) {
        $css = ':root {' . "\n";

        foreach ($colors as $name => $value) {
            $css .= "  --flavor-{$name}: {$value};\n";
        }

        $css .= "}\n";

        return $css;
    }
}
