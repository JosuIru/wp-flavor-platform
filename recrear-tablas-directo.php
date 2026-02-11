<?php
/**
 * Script para recrear tablas directamente usando get_table_schema() de cada módulo
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/recrear-tablas-directo.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Recrear Tablas Directamente</h1>';

global $wpdb;

// Módulos que necesitan recrear tablas
$modulos_config = [
    'trading_ia' => [
        'path' => 'trading-ia',
        'class' => 'Flavor_Chat_Trading_IA_Module',
        'tablas' => ['wp_flavor_trading_ia_trades', 'wp_flavor_trading_ia_portfolio', 'wp_flavor_trading_ia_reglas', 'wp_flavor_trading_ia_alertas']
    ],
    'biblioteca' => [
        'path' => 'biblioteca',
        'class' => 'Flavor_Chat_Biblioteca_Module',
        'tablas' => ['wp_flavor_biblioteca_libros', 'wp_flavor_biblioteca_prestamos', 'wp_flavor_biblioteca_reservas', 'wp_flavor_biblioteca_valoraciones']
    ],
    'avisos_municipales' => [
        'path' => 'avisos-municipales',
        'class' => 'Flavor_Chat_Avisos_Municipales_Module',
        'tablas' => ['wp_flavor_avisos_municipales', 'wp_flavor_avisos_adjuntos', 'wp_flavor_avisos_visualizaciones', 'wp_flavor_avisos_confirmaciones', 'wp_flavor_avisos_push_subscriptions']
    ],
    'tramites' => [
        'path' => 'tramites',
        'class' => 'Flavor_Chat_Tramites_Module',
        'tablas' => ['wp_flavor_tipos_tramite', 'wp_flavor_expedientes', 'wp_flavor_documentos_expediente', 'wp_flavor_estados_tramite', 'wp_flavor_campos_formulario', 'wp_flavor_historial_expediente']
    ],
    'carpooling' => [
        'path' => 'carpooling',
        'class' => 'Flavor_Chat_Carpooling_Module',
        'tablas' => ['wp_flavor_carpooling_viajes', 'wp_flavor_carpooling_reservas', 'wp_flavor_carpooling_rutas_recurrentes', 'wp_flavor_carpooling_valoraciones', 'wp_flavor_carpooling_vehiculos']
    ]
];

echo '<h2>Módulos a procesar:</h2>';
echo '<ul>';
foreach ($modulos_config as $modulo_id => $config) {
    echo "<li>{$modulo_id} - " . count($config['tablas']) . " tablas</li>";
}
echo '</ul>';

// Botón para ejecutar
echo '<hr>';
echo '<form method="post" style="margin: 20px 0;">';
echo '<input type="hidden" name="action" value="recrear_tablas_directo">';
wp_nonce_field('recrear_tablas_directo', 'recrear_tablas_nonce');
echo '<button type="submit" class="button button-primary button-large" style="background: #dc2626;">Eliminar y Recrear Tablas</button>';
echo '<p style="color: #dc2626;"><strong>⚠ ADVERTENCIA:</strong> Esto eliminará los datos existentes en estas tablas.</p>';
echo '</form>';

// Procesar recreación
if (isset($_POST['action']) && $_POST['action'] === 'recrear_tablas_directo') {
    check_admin_referer('recrear_tablas_directo', 'recrear_tablas_nonce');

    echo '<div style="background: #fff; border-left: 4px solid #2563eb; padding: 12px; margin: 20px 0;">';
    echo '<h3>Proceso de Recreación</h3>';

    $total_eliminadas = 0;
    $total_creadas = 0;
    $errores = [];

    foreach ($modulos_config as $modulo_id => $config) {
        echo "<h4>Módulo: {$modulo_id}</h4>";

        // 1. Eliminar tablas existentes
        echo '<p>Eliminando tablas...</p>';
        echo '<ul>';
        foreach ($config['tablas'] as $tabla) {
            $resultado = $wpdb->query("DROP TABLE IF EXISTS `$tabla`");
            if ($resultado !== false) {
                echo "<li style='color: orange;'>✓ Eliminada: {$tabla}</li>";
                $total_eliminadas++;
            } else {
                echo "<li style='color: red;'>✗ Error eliminando: {$tabla}</li>";
            }
        }
        echo '</ul>';

        // 2. Cargar módulo
        $module_file = FLAVOR_CHAT_IA_PATH . 'includes/modules/' . $config['path'] . '/class-' . $config['path'] . '-module.php';

        if (!file_exists($module_file)) {
            echo "<p style='color: red;'>✗ Archivo no encontrado: {$module_file}</p>";
            $errores[] = "{$modulo_id}: Archivo no encontrado";
            continue;
        }

        require_once $module_file;

        if (!class_exists($config['class'])) {
            echo "<p style='color: red;'>✗ Clase no encontrada: {$config['class']}</p>";
            $errores[] = "{$modulo_id}: Clase no encontrada";
            continue;
        }

        // 3. Crear instancia y obtener esquema
        try {
            $instancia = new $config['class']();

            if (!method_exists($instancia, 'get_table_schema')) {
                echo "<p style='color: red;'>✗ Método get_table_schema() no existe en {$config['class']}</p>";
                $errores[] = "{$modulo_id}: Método get_table_schema() no existe";
                continue;
            }

            $esquemas = $instancia->get_table_schema();

            if (empty($esquemas)) {
                echo "<p style='color: red;'>✗ get_table_schema() devolvió array vacío</p>";
                $errores[] = "{$modulo_id}: Esquema vacío";
                continue;
            }

            echo "<p>Creando " . count($esquemas) . " tablas...</p>";
            echo '<ul>';

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            foreach ($esquemas as $nombre_tabla => $sql) {
                $resultado = dbDelta($sql);

                if (!empty($resultado)) {
                    echo "<li style='color: green;'>✓ Creada: {$nombre_tabla}</li>";
                    $total_creadas++;
                } else {
                    echo "<li style='color: red;'>✗ Error creando: {$nombre_tabla}</li>";
                    $errores[] = "{$modulo_id}: Error al crear {$nombre_tabla}";
                }
            }
            echo '</ul>';

        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
            $errores[] = "{$modulo_id}: " . $e->getMessage();
        }

        echo '<hr>';
    }

    echo "<h3>Resumen</h3>";
    echo "<p>Tablas eliminadas: {$total_eliminadas}</p>";
    echo "<p>Tablas creadas: {$total_creadas}</p>";

    if (!empty($errores)) {
        echo "<h4 style='color: red;'>Errores:</h4>";
        echo '<ul>';
        foreach ($errores as $error) {
            echo "<li style='color: red;'>{$error}</li>";
        }
        echo '</ul>';
    }

    echo '</div>';

    // 3. Verificar columnas
    echo '<h3>Verificación de Columnas</h3>';

    $verificaciones = [
        'wp_flavor_trading_ia_trades' => ['id', 'estado', 'precio_entrada', 'fecha_apertura'],
        'wp_flavor_biblioteca_libros' => ['id', 'titulo', 'estado', 'propietario_id'],
        'wp_flavor_avisos_municipales' => ['id', 'titulo', 'estado', 'fecha_expiracion'],
        'wp_flavor_expedientes' => ['id', 'numero_expediente', 'estado', 'fecha_resolucion'],
        'wp_flavor_carpooling_viajes' => ['id', 'conductor_id', 'estado', 'fecha_salida']
    ];

    echo '<ul>';
    foreach ($verificaciones as $tabla => $columnas_esperadas) {
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            echo "<li style='color: red;'>✗ {$tabla}: Tabla no existe</li>";
            continue;
        }

        $columnas_existentes = $wpdb->get_col("SHOW COLUMNS FROM `$tabla`");
        $todas_presentes = true;
        $faltantes = [];

        foreach ($columnas_esperadas as $col) {
            if (!in_array($col, $columnas_existentes)) {
                $todas_presentes = false;
                $faltantes[] = $col;
            }
        }

        if ($todas_presentes) {
            echo "<li style='color: green;'>✓ {$tabla}: Todas las columnas presentes (" . count($columnas_existentes) . " columnas totales)</li>";
        } else {
            echo "<li style='color: red;'>✗ {$tabla}: Faltan columnas: " . implode(', ', $faltantes) . "</li>";
            echo "<ul><li style='color: gray;'>Columnas existentes: " . implode(', ', $columnas_existentes) . "</li></ul>";
        }
    }
    echo '</ul>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">← Volver al Compositor</a></p>';
