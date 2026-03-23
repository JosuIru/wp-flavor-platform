<?php
/**
 * Script para añadir carga de frontend controllers en módulos AMARILLO
 * Versión 2 - Usa PHP para mejor manipulación del código
 */

if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

$plugin_path = '/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia';

// Módulos a procesar
$modulos = [
    'advertising' => 'Advertising',
    'agregador-contenido' => 'Agregador_Contenido',
    'bares' => 'Bares',
    'chat-estados' => 'Chat_Estados',
    'clientes' => 'Clientes',
    'contabilidad' => 'Contabilidad',
    'crowdfunding' => 'Crowdfunding',
    'dex-solana' => 'Dex_Solana',
    'economia-suficiencia' => 'Economia_Suficiencia',
    'email-marketing' => 'Email_Marketing',
    'empresarial' => 'Empresarial',
    'encuestas' => 'Encuestas',
    'energia-comunitaria' => 'Energia_Comunitaria',
    'facturas' => 'Facturas',
    'huella-ecologica' => 'Huella_Ecologica',
    'kulturaka' => 'Kulturaka',
    'red-social' => 'Red_Social',
    'sello-conciencia' => 'Sello_Conciencia',
    'themacle' => 'Themacle',
    'trading-ia' => 'Trading_IA',
    'woocommerce' => 'Woocommerce',
];

$procesados = 0;
$modificados = 0;
$saltados = 0;
$errores = 0;

echo "=========================================\n";
echo " ACTIVADOR DE FRONTEND CONTROLLERS V2\n";
echo "=========================================\n\n";
echo "Total módulos: " . count($modulos) . "\n\n";

foreach ($modulos as $slug => $class_name) {
    $procesados++;
    $module_file = "{$plugin_path}/includes/modules/{$slug}/class-{$slug}-module.php";

    echo "[{$procesados}/" . count($modulos) . "] Procesando: {$slug}\n";

    if (!file_exists($module_file)) {
        echo "  ⚠ Archivo no existe\n";
        $saltados++;
        continue;
    }

    // Leer contenido
    $content = file_get_contents($module_file);

    // Verificar si ya tiene el método
    if (strpos($content, 'cargar_frontend_controller') !== false) {
        echo "  ⚠ Ya tiene el método, saltando...\n";
        $saltados++;
        continue;
    }

    // Backup
    copy($module_file, $module_file . '.bak');

    // Preparar el método a añadir
    $metodo = "
    /**
     * Cargar frontend controller
     */
    private function cargar_frontend_controller() {
        \$archivo_controller = dirname(__FILE__) . '/frontend/class-{$slug}-frontend-controller.php';
        if (file_exists(\$archivo_controller)) {
            require_once \$archivo_controller;
            Flavor_{$class_name}_Frontend_Controller::get_instance();
        }
    }
";

    // Buscar la posición del último }
    $last_brace_pos = strrpos($content, '}');

    if ($last_brace_pos === false) {
        echo "  ❌ No se encontró llave de cierre\n";
        $errores++;
        continue;
    }

    // Insertar el método antes del último }
    $new_content = substr($content, 0, $last_brace_pos) . $metodo . "\n" . substr($content, $last_brace_pos);

    // Ahora añadir la llamada en el constructor
    // Buscar parent::__construct(); y añadir después
    if (preg_match('/(parent::__construct\(\);)\s*\n/', $new_content, $matches, PREG_OFFSET_CAPTURE)) {
        $insert_pos = $matches[0][1] + strlen($matches[0][0]);
        $new_content = substr($new_content, 0, $insert_pos) .
                      "        \$this->cargar_frontend_controller();\n" .
                      substr($new_content, $insert_pos);

        // Escribir archivo
        file_put_contents($module_file, $new_content);

        // Verificar sintaxis
        exec("php -l " . escapeshellarg($module_file) . " 2>&1", $output, $return_code);
        if ($return_code !== 0) {
            echo "  ❌ Error de sintaxis PHP\n";
            // Restaurar backup
            copy($module_file . '.bak', $module_file);
            $errores++;
        } else {
            echo "  ✅ Modificado y verificado\n";
            $modificados++;
        }
    } else {
        echo "  ⚠ No se encontró parent::__construct()\n";
        // Restaurar backup
        copy($module_file . '.bak', $module_file);
        $errores++;
    }
}

echo "\n=========================================\n";
echo " RESUMEN\n";
echo "=========================================\n";
echo "Procesados: {$procesados}\n";
echo "Modificados: {$modificados}\n";
echo "Saltados: {$saltados}\n";
echo "Errores: {$errores}\n\n";

if ($modificados > 0) {
    echo "✅ Proceso completado\n\n";
    echo "Backups: *.bak\n\n";
    echo "SIGUIENTE PASO:\n";
    echo "  wp plugin deactivate flavor-chat-ia\n";
    echo "  wp plugin activate flavor-chat-ia\n";
} else {
    echo "ℹ️ No se realizaron modificaciones\n";
}
