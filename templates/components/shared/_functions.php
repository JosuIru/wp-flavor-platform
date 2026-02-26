<?php
/**
 * Funciones Helper para Componentes Shared
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene la clase de color para un gradiente Tailwind
 *
 * @param string $color Color base (red, green, blue, yellow, etc.)
 * @return array Classes para from y to del gradiente
 */
function flavor_get_gradient_classes(string $color): array {
    $gradientes = [
        'red'     => ['from' => 'from-red-500', 'to' => 'to-rose-500'],
        'green'   => ['from' => 'from-lime-500', 'to' => 'to-green-600'],
        'blue'    => ['from' => 'from-blue-500', 'to' => 'to-indigo-600'],
        'yellow'  => ['from' => 'from-amber-400', 'to' => 'to-orange-500'],
        'purple'  => ['from' => 'from-purple-500', 'to' => 'to-pink-500'],
        'cyan'    => ['from' => 'from-cyan-400', 'to' => 'to-teal-500'],
        'orange'  => ['from' => 'from-orange-400', 'to' => 'to-red-500'],
        'teal'    => ['from' => 'from-teal-400', 'to' => 'to-emerald-600'],
        'pink'    => ['from' => 'from-pink-400', 'to' => 'to-rose-500'],
        'indigo'  => ['from' => 'from-indigo-500', 'to' => 'to-purple-600'],
        'gray'    => ['from' => 'from-gray-500', 'to' => 'to-slate-600'],
    ];

    return $gradientes[$color] ?? $gradientes['blue'];
}

/**
 * Obtiene classes de color para badges/elementos según el color
 *
 * @param string $color Color (red, green, blue, yellow, etc.)
 * @return array Classes bg, text, hover
 */
function flavor_get_color_classes(string $color): array {
    $colores = [
        'red'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'hover' => 'hover:bg-red-200', 'bg_solid' => 'bg-red-500'],
        'green'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'hover' => 'hover:bg-green-200', 'bg_solid' => 'bg-green-500'],
        'lime'    => ['bg' => 'bg-lime-100', 'text' => 'text-lime-700', 'hover' => 'hover:bg-lime-200', 'bg_solid' => 'bg-lime-500'],
        'blue'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'hover' => 'hover:bg-blue-200', 'bg_solid' => 'bg-blue-500'],
        'yellow'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'hover' => 'hover:bg-yellow-200', 'bg_solid' => 'bg-yellow-500'],
        'purple'  => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'hover' => 'hover:bg-purple-200', 'bg_solid' => 'bg-purple-500'],
        'cyan'    => ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-700', 'hover' => 'hover:bg-cyan-200', 'bg_solid' => 'bg-cyan-500'],
        'orange'  => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'hover' => 'hover:bg-orange-200', 'bg_solid' => 'bg-orange-500'],
        'gray'    => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'hover' => 'hover:bg-gray-200', 'bg_solid' => 'bg-gray-500'],
    ];

    return $colores[$color] ?? $colores['blue'];
}

/**
 * Renderiza un componente shared
 *
 * @param string $component Nombre del componente (sin .php)
 * @param array  $args      Argumentos para el componente
 * @return void
 */
function flavor_render_component(string $component, array $args = []): void {
    $file = FLAVOR_PLUGIN_PATH . "templates/components/shared/{$component}.php";

    if (!file_exists($file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "<!-- Componente no encontrado: {$component} -->";
        }
        return;
    }

    extract($args, EXTR_SKIP);
    include $file;
}

/**
 * Obtiene el contenido de un componente shared como string
 *
 * @param string $component Nombre del componente (sin .php)
 * @param array  $args      Argumentos para el componente
 * @return string HTML del componente
 */
function flavor_get_component(string $component, array $args = []): string {
    ob_start();
    flavor_render_component($component, $args);
    return ob_get_clean();
}

/**
 * Genera un ID único para elementos interactivos
 *
 * @param string $prefix Prefijo opcional
 * @return string ID único
 */
function flavor_unique_id(string $prefix = 'fl'): string {
    static $counter = 0;
    $counter++;
    return $prefix . '_' . $counter . '_' . wp_rand(100, 999);
}

/**
 * Sanitiza y valida la configuración de un componente
 *
 * @param array $args     Argumentos recibidos
 * @param array $defaults Valores por defecto
 * @return array Argumentos sanitizados
 */
function flavor_parse_component_args(array $args, array $defaults): array {
    $parsed = wp_parse_args($args, $defaults);

    // Sanitizar strings comunes
    if (isset($parsed['title'])) {
        $parsed['title'] = sanitize_text_field($parsed['title']);
    }
    if (isset($parsed['subtitle'])) {
        $parsed['subtitle'] = sanitize_text_field($parsed['subtitle']);
    }
    if (isset($parsed['color'])) {
        $parsed['color'] = sanitize_key($parsed['color']);
    }

    return $parsed;
}
