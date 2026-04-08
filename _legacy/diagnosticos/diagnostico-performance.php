<?php
/**
 * Diagnóstico de Performance
 *
 * Activar añadiendo ?flavor_perf=1 a cualquier URL
 */

if (!defined('ABSPATH')) exit;

// Solo para admins con parámetro específico
if (!isset($_GET['flavor_perf']) || !current_user_can('manage_options')) {
    return;
}

add_action('shutdown', function() {
    global $wpdb;

    echo "\n\n<!--\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   DIAGNÓSTICO DE PERFORMANCE - FLAVOR PLATFORM\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";

    // 1. Consultas de base de datos
    echo "1. CONSULTAS SQL\n";
    echo "   Total consultas: " . $wpdb->num_queries . "\n";
    echo "   Tiempo total SQL: " . (isset($wpdb->query_time) ? round($wpdb->query_time, 4) . 's' : 'N/A') . "\n\n";

    // Top 10 consultas más lentas
    if (defined('SAVEQUERIES') && SAVEQUERIES && !empty($wpdb->queries)) {
        echo "   Top 10 consultas más lentas:\n";
        $sorted_queries = $wpdb->queries;
        usort($sorted_queries, function($a, $b) {
            return $b[1] <=> $a[1];
        });

        foreach (array_slice($sorted_queries, 0, 10) as $i => $query) {
            echo "   " . ($i+1) . ". " . round($query[1] * 1000, 2) . "ms - " . substr($query[0], 0, 100) . "...\n";
        }
        echo "\n";
    }

    // 2. Memoria
    echo "2. MEMORIA\n";
    echo "   Uso actual: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    echo "   Pico de uso: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    echo "   Límite PHP: " . ini_get('memory_limit') . "\n\n";

    // 3. Hooks
    global $wp_filter;
    $total_hooks = 0;
    $hooks_pesados = [];

    foreach ($wp_filter as $hook_name => $hook) {
        $count = 0;
        if (is_object($hook) && isset($hook->callbacks)) {
            foreach ($hook->callbacks as $priority => $callbacks) {
                $count += count($callbacks);
            }
        }
        $total_hooks += $count;

        if ($count > 10) {
            $hooks_pesados[$hook_name] = $count;
        }
    }

    echo "3. HOOKS Y FILTROS\n";
    echo "   Total hooks registrados: " . $total_hooks . "\n";
    echo "   Hooks con más de 10 callbacks:\n";
    arsort($hooks_pesados);
    foreach (array_slice($hooks_pesados, 0, 10, true) as $hook => $count) {
        echo "   - {$hook}: {$count} callbacks\n";
    }
    echo "\n";

    // 4. Archivos cargados
    $files = get_included_files();
    echo "4. ARCHIVOS PHP CARGADOS\n";
    echo "   Total archivos: " . count($files) . "\n";

    $flavor_files = array_filter($files, function($file) {
        return strpos($file, 'flavor-chat-ia') !== false;
    });
    echo "   Archivos de Flavor: " . count($flavor_files) . "\n\n";

    // 5. Módulos
    if (class_exists('Flavor_Chat_Module_Loader')) {
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $active = Flavor_Chat_Module_Loader::get_active_modules_cached();
        $loaded = $loader->get_loaded_modules();

        echo "5. MÓDULOS\n";
        echo "   Activos: " . count($active) . "\n";
        echo "   Cargados: " . count($loaded) . "\n";
        echo "   Lista: " . implode(', ', $active) . "\n\n";
    }

    // 6. Transients
    $transients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    echo "6. TRANSIENTS\n";
    echo "   Total en BD: " . $transients . "\n\n";

    // 7. Autoload
    $autoload_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
    echo "7. AUTOLOAD\n";
    echo "   Tamaño total: " . round($autoload_size / 1024, 2) . " KB\n\n";

    // 8. Scripts y estilos
    global $wp_scripts, $wp_styles;
    echo "8. ASSETS\n";
    echo "   Scripts encolados: " . (isset($wp_scripts->queue) ? count($wp_scripts->queue) : 0) . "\n";
    echo "   Estilos encolados: " . (isset($wp_styles->queue) ? count($wp_styles->queue) : 0) . "\n\n";

    // 9. Tiempo de ejecución
    echo "9. TIEMPO DE EJECUCIÓN\n";
    $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    echo "   Total: " . round($time * 1000, 2) . " ms\n\n";

    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "-->\n";
}, PHP_INT_MAX);
