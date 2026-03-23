<?php
/**
 * Script para añadir carga de frontend controllers en módulos faltantes
 * Versión 3 - Para los 29 módulos que tienen frontend controller pero no lo cargan
 */

if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

$plugin_path = '/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia';

// Módulos que tienen frontend controller pero no lo cargan
$modulos = [
    'avisos-municipales' => 'Avisos_Municipales',
    'banco-tiempo' => 'Banco_Tiempo',
    'bicicletas-compartidas' => 'Bicicletas_Compartidas',
    'biodiversidad-local' => 'Biodiversidad_Local',
    'campanias' => 'Campanias',
    'circulos-cuidados' => 'Circulos_Cuidados',
    'compostaje' => 'Compostaje',
    'comunidades' => 'Comunidades',
    'documentacion-legal' => 'Documentacion_Legal',
    'economia-don' => 'Economia_Don',
    'empresas' => 'Empresas',
    'espacios-comunes' => 'Espacios_Comunes',
    'fichaje-empleados' => 'Fichaje_Empleados',
    'foros' => 'Foros',
    'grupos-consumo' => 'Chat_Grupos_Consumo',
    'huertos-urbanos' => 'Huertos_Urbanos',
    'justicia-restaurativa' => 'Justicia_Restaurativa',
    'mapa-actores' => 'Mapa_Actores',
    'multimedia' => 'Multimedia',
    'parkings' => 'Parkings',
    'podcast' => 'Podcast',
    'presupuestos-participativos' => 'Presupuestos_Participativos',
    'radio' => 'Radio',
    'recetas' => 'Recetas',
    'reciclaje' => 'Reciclaje',
    'saberes-ancestrales' => 'Saberes_Ancestrales',
    'seguimiento-denuncias' => 'Seguimiento_Denuncias',
    'trabajo-digno' => 'Trabajo_Digno',
    'transparencia' => 'Transparencia',
];

$procesados = 0;
$modificados = 0;
$saltados = 0;
$errores = 0;

echo "=========================================\n";
echo " ACTIVADOR DE FRONTEND CONTROLLERS V3\n";
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
    if (preg_match('/(parent::__construct\(\);)(\s*\n)/', $new_content, $matches, PREG_OFFSET_CAPTURE)) {
        $insert_pos = $matches[0][1] + strlen($matches[0][0]);

        // Añadir la llamada con la indentación apropiada
        $new_content = substr($new_content, 0, $insert_pos) .
                      "        \$this->cargar_frontend_controller();\n\n" .
                      substr($new_content, $insert_pos);

        // Escribir archivo
        file_put_contents($module_file, $new_content);

        // Verificar sintaxis
        exec("php -l " . escapeshellarg($module_file) . " 2>&1", $output, $return_code);
        if ($return_code !== 0) {
            echo "  ❌ Error de sintaxis PHP\n";
            echo "     " . implode("\n     ", $output) . "\n";
            // Restaurar backup
            copy($module_file . '.bak', $module_file);
            $errores++;
        } else {
            echo "  ✅ Modificado y verificado\n";
            $modificados++;
            // Eliminar backup
            unlink($module_file . '.bak');
        }
    } else {
        echo "  ⚠ No se encontró parent::__construct()\n";
        // Restaurar backup
        copy($module_file . '.bak', $module_file);
        unlink($module_file . '.bak');
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
    echo "SIGUIENTE PASO:\n";
    echo "  cd /home/josu/Local Sites/sitio-prueba/app/public\n";
    echo "  wp plugin deactivate flavor-chat-ia\n";
    echo "  wp plugin activate flavor-chat-ia\n";
} else {
    echo "ℹ️ No se realizaron modificaciones\n";
}
