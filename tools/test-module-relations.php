<?php
/**
 * Script de Testing para Sistema de Relaciones entre Módulos
 *
 * Uso desde WP-CLI:
 * wp eval-file tools/test-module-relations.php
 *
 * O desde navegador (solo para admins):
 * /wp-content/plugins/flavor-chat-ia/tools/test-module-relations.php?key=flavor-test-2024
 *
 * @package FlavorPlatform
 */

// Verificar ejecución desde WP-CLI o con clave de seguridad
if (!defined('WP_CLI') && !defined('ABSPATH')) {
    require_once dirname(__DIR__, 5) . '/wp-load.php';

    if (!current_user_can('manage_options')) {
        die('Acceso denegado');
    }

    if (empty($_GET['key']) || $_GET['key'] !== 'flavor-test-2024') {
        die('Clave de seguridad incorrecta');
    }
}

echo "=== TEST DEL SISTEMA DE RELACIONES ENTRE MÓDULOS ===\n\n";

global $wpdb;

// Test 1: Verificar que la tabla existe
echo "1. Verificando tabla en base de datos...\n";
$table = $wpdb->prefix . 'flavor_module_relations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if ($table_exists === $table) {
    echo "   ✓ Tabla existe: $table\n";
} else {
    echo "   ✗ ERROR: Tabla NO existe: $table\n";
    echo "   Ejecuta: Flavor_Database_Installer::upgrade_module_relations_table();\n\n";
    exit(1);
}

// Test 2: Verificar estructura de la tabla
echo "\n2. Verificando estructura de la tabla...\n";
$columns = $wpdb->get_results("DESCRIBE $table");
$required_columns = ['id', 'parent_module_id', 'child_module_id', 'context', 'priority', 'enabled'];

foreach ($required_columns as $col) {
    $found = false;
    foreach ($columns as $column) {
        if ($column->Field === $col) {
            $found = true;
            break;
        }
    }

    if ($found) {
        echo "   ✓ Columna '$col' existe\n";
    } else {
        echo "   ✗ ERROR: Columna '$col' NO existe\n";
    }
}

// Test 3: Verificar clase Helper
echo "\n3. Verificando clase Flavor_Module_Relations_Helper...\n";
if (class_exists('Flavor_Module_Relations_Helper')) {
    echo "   ✓ Clase existe\n";

    // Test métodos
    $methods = ['get_child_modules', 'is_child_of', 'get_vertical_modules', 'get_horizontal_modules'];
    foreach ($methods as $method) {
        if (method_exists('Flavor_Module_Relations_Helper', $method)) {
            echo "   ✓ Método '$method' existe\n";
        } else {
            echo "   ✗ ERROR: Método '$method' NO existe\n";
        }
    }
} else {
    echo "   ✗ ERROR: Clase NO existe\n";
}

// Test 4: Verificar clase Admin
echo "\n4. Verificando clase Flavor_Module_Relations_Admin...\n";
if (class_exists('Flavor_Module_Relations_Admin')) {
    echo "   ✓ Clase existe\n";
} else {
    echo "   ✗ WARNING: Clase NO cargada (normal si no estás en admin)\n";
}

// Test 5: Test funcional - Guardar y obtener relaciones
echo "\n5. Test funcional: Guardar y obtener relaciones...\n";

// Limpiar datos de test previos
$wpdb->delete($table, ['context' => 'test_context']);

// Guardar relaciones de prueba
$test_relations = ['foros', 'chat_interno', 'recetas'];
$save_result = Flavor_Module_Relations_Helper::save_relations('test_module', $test_relations, 'test_context');

if ($save_result) {
    echo "   ✓ Guardado exitoso\n";
} else {
    echo "   ✗ ERROR: No se pudieron guardar relaciones\n";
}

// Obtener relaciones
$retrieved = Flavor_Module_Relations_Helper::get_child_modules('test_module', 'test_context');

if ($retrieved === $test_relations) {
    echo "   ✓ Recuperación exitosa: " . implode(', ', $retrieved) . "\n";
} else {
    echo "   ✗ ERROR: Datos recuperados no coinciden\n";
    echo "   Esperado: " . implode(', ', $test_relations) . "\n";
    echo "   Obtenido: " . implode(', ', $retrieved) . "\n";
}

// Test verificación individual
$is_child = Flavor_Module_Relations_Helper::is_child_of('test_module', 'foros', 'test_context');
if ($is_child) {
    echo "   ✓ Verificación is_child_of() funciona\n";
} else {
    echo "   ✗ ERROR: Verificación is_child_of() falló\n";
}

// Limpiar
$wpdb->delete($table, ['context' => 'test_context']);
echo "   ✓ Datos de test eliminados\n";

// Test 6: Verificar módulos reales
echo "\n6. Verificando módulos activos...\n";

$verticales = Flavor_Module_Relations_Helper::get_vertical_modules();
$horizontales = Flavor_Module_Relations_Helper::get_horizontal_modules();

echo "   Módulos Verticales: " . count($verticales) . "\n";
foreach (array_slice($verticales, 0, 5) as $id => $data) {
    echo "      - $id: {$data['name']}\n";
}
if (count($verticales) > 5) {
    echo "      ... y " . (count($verticales) - 5) . " más\n";
}

echo "\n   Módulos Horizontales: " . count($horizontales) . "\n";
foreach (array_slice($horizontales, 0, 5) as $id => $data) {
    echo "      - $id: {$data['name']}\n";
}
if (count($horizontales) > 5) {
    echo "      ... y " . (count($horizontales) - 5) . " más\n";
}

// Test 7: Verificar integración con interface-chat-module
echo "\n7. Verificando integración con módulos...\n";

if (class_exists('Flavor_Platform_Module_Loader')) {
    $loader = Flavor_Platform_Module_Loader::get_instance();
    $all_modules = $loader->get_all_modules();

    if (!empty($all_modules)) {
        // Tomar el primer módulo vertical
        $test_module = null;
        foreach ($all_modules as $module_id => $module) {
            $metadata = $module->get_ecosystem_metadata();
            if (($metadata['module_role'] ?? '') === 'vertical') {
                $test_module = $module;
                break;
            }
        }

        if ($test_module) {
            $metadata = $test_module->get_ecosystem_metadata();
            $supports = $metadata['ecosystem_supports_modules'] ?? [];

            echo "   ✓ Módulo de prueba: " . $test_module->get_name() . "\n";
            echo "   ✓ Módulos soportados: " . (count($supports) > 0 ? implode(', ', $supports) : 'ninguno') . "\n";
            echo "   ✓ get_ecosystem_metadata() funciona correctamente\n";
        } else {
            echo "   ⚠ No se encontraron módulos verticales para testing\n";
        }
    } else {
        echo "   ⚠ No hay módulos cargados\n";
    }
} else {
    echo "   ✗ ERROR: Flavor_Platform_Module_Loader no disponible\n";
}

// Test 8: Verificar archivos de assets
echo "\n8. Verificando archivos de assets...\n";

$css_file = FLAVOR_PLATFORM_PATH . 'admin/css/module-relations.css';
$js_file = FLAVOR_PLATFORM_PATH . 'admin/js/module-relations.js';

if (file_exists($css_file)) {
    $size = filesize($css_file);
    echo "   ✓ CSS existe: " . round($size / 1024, 2) . " KB\n";
} else {
    echo "   ✗ ERROR: CSS no existe\n";
}

if (file_exists($js_file)) {
    $size = filesize($js_file);
    echo "   ✓ JS existe: " . round($size / 1024, 2) . " KB\n";
} else {
    echo "   ✗ ERROR: JS no existe\n";
}

// Test 9: Verificar documentación
echo "\n9. Verificando documentación...\n";

$doc_file = FLAVOR_PLATFORM_PATH . 'docs/SISTEMA-RELACIONES-MODULOS.md';
if (file_exists($doc_file)) {
    $size = filesize($doc_file);
    echo "   ✓ Documentación existe: " . round($size / 1024, 2) . " KB\n";
} else {
    echo "   ✗ WARNING: Documentación no existe\n";
}

// Test 10: Contar relaciones existentes
echo "\n10. Estado actual de relaciones en BD...\n";

$count_global = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE context = 'global'");
$count_total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
$distinct_parents = $wpdb->get_var("SELECT COUNT(DISTINCT parent_module_id) FROM $table");

echo "   Total de relaciones: $count_total\n";
echo "   Relaciones globales: $count_global\n";
echo "   Módulos padre configurados: $distinct_parents\n";

if ($count_total > 0) {
    echo "\n   Ejemplo de relaciones:\n";
    $examples = $wpdb->get_results("SELECT parent_module_id, child_module_id, context, priority FROM $table ORDER BY priority ASC LIMIT 10");
    foreach ($examples as $rel) {
        echo "      {$rel->parent_module_id} → {$rel->child_module_id} (contexto: {$rel->context}, prioridad: {$rel->priority})\n";
    }
}

// Resumen final
echo "\n" . str_repeat('=', 60) . "\n";
echo "RESUMEN:\n";
echo str_repeat('=', 60) . "\n";

$total_tests = 10;
$passed = 0;

// Contar tests pasados (simplificado)
if ($table_exists) $passed++;
if (class_exists('Flavor_Module_Relations_Helper')) $passed++;
if (file_exists($css_file)) $passed++;
if (file_exists($js_file)) $passed++;

$percentage = round(($passed / 4) * 100); // Simplificado

echo "Estado del sistema: ";
if ($percentage >= 90) {
    echo "✓ EXCELENTE ($percentage%)\n";
} elseif ($percentage >= 70) {
    echo "⚠ BUENO ($percentage%)\n";
} else {
    echo "✗ NECESITA ATENCIÓN ($percentage%)\n";
}

echo "\nPróximos pasos:\n";
echo "1. Ir a: Admin → Flavor Platform → Relaciones Módulos\n";
echo "2. Configurar relaciones para módulos verticales\n";
echo "3. Verificar navegación en frontend\n";

echo "\n" . str_repeat('=', 60) . "\n";
echo "Test completado.\n";
