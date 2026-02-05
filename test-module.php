<?php
/**
 * Script de test para verificar el módulo Grupos de Consumo
 */

// Simular constante ABSPATH
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// Simular funciones de WordPress necesarias
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_n_noop')) {
    function _n_noop($singular, $plural, $domain = null) {
        return array('singular' => $singular, 'plural' => $plural, 'domain' => $domain);
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags($str);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return strip_tags($str);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs(intval($value));
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        return $checked == $current ? ' checked="checked"' : '';
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        return $selected == $current ? ' selected="selected"' : '';
    }
}

echo "=== Test de Sintaxis de Módulo Grupos de Consumo ===\n\n";

// Cargar la interfaz
echo "1. Cargando interfaz del módulo...\n";
$interface_file = __DIR__ . '/includes/modules/interface-chat-module.php';
if (file_exists($interface_file)) {
    require_once $interface_file;
    echo "   ✓ Interfaz cargada correctamente\n\n";
} else {
    echo "   ✗ ERROR: No se encuentra la interfaz\n";
    exit(1);
}

// Cargar la API
echo "2. Cargando API REST...\n";
$api_file = __DIR__ . '/includes/modules/grupos-consumo/class-grupos-consumo-api.php';
if (file_exists($api_file)) {
    require_once $api_file;
    echo "   ✓ API cargada correctamente\n\n";
} else {
    echo "   ✗ ERROR: No se encuentra la API\n";
    exit(1);
}

// Cargar el módulo
echo "3. Cargando módulo...\n";
$module_file = __DIR__ . '/includes/modules/grupos-consumo/class-grupos-consumo-module.php';
if (file_exists($module_file)) {
    require_once $module_file;
    echo "   ✓ Módulo cargado correctamente\n\n";
} else {
    echo "   ✗ ERROR: No se encuentra el módulo\n";
    exit(1);
}

// Verificar que las clases existen
echo "4. Verificando clases...\n";
if (interface_exists('Flavor_Chat_Module_Interface')) {
    echo "   ✓ Interfaz Flavor_Chat_Module_Interface existe\n";
} else {
    echo "   ✗ ERROR: Interfaz Flavor_Chat_Module_Interface no existe\n";
    exit(1);
}

if (class_exists('Flavor_Chat_Module_Base')) {
    echo "   ✓ Clase base Flavor_Chat_Module_Base existe\n";
} else {
    echo "   ✗ ERROR: Clase base Flavor_Chat_Module_Base no existe\n";
    exit(1);
}

if (class_exists('Flavor_Grupos_Consumo_API')) {
    echo "   ✓ Clase Flavor_Grupos_Consumo_API existe\n";
} else {
    echo "   ✗ ERROR: Clase Flavor_Grupos_Consumo_API no existe\n";
    exit(1);
}

if (class_exists('Flavor_Chat_Grupos_Consumo_Module')) {
    echo "   ✓ Clase Flavor_Chat_Grupos_Consumo_Module existe\n";
} else {
    echo "   ✗ ERROR: Clase Flavor_Chat_Grupos_Consumo_Module no existe\n";
    exit(1);
}

echo "\n=== TODAS LAS PRUEBAS PASARON ✓ ===\n";
echo "\nEl módulo no tiene errores de sintaxis PHP.\n";
echo "Si hay un error fatal, probablemente es por:\n";
echo "  - Una dependencia de WordPress que no está disponible\n";
echo "  - Un conflicto con otro plugin\n";
echo "  - Un problema con la base de datos\n\n";
