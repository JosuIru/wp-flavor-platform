<?php
/**
 * Diagnóstico de Presupuestos Participativos
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

echo "<h1>Diagnóstico Presupuestos Participativos</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";

global $wpdb;

// 1. Verificar tablas
echo "<h2>1. Tablas de BD</h2>";
$tablas = ['flavor_pp_ediciones', 'flavor_pp_proyectos', 'flavor_pp_votos', 'flavor_pp_categorias'];
foreach ($tablas as $tabla) {
    $nombre_completo = $wpdb->prefix . $tabla;
    $existe = $wpdb->get_var("SHOW TABLES LIKE '{$nombre_completo}'");
    $estado = $existe ? 'ok' : 'error';
    $texto = $existe ? '✅ Existe' : '❌ NO existe';
    echo "<p class='{$estado}'>{$tabla}: {$texto}</p>";

    if (!$existe) {
        // Crear tabla
        echo "<p>Intentando crear {$tabla}...</p>";
    }
}

// 2. Crear tablas si no existen
echo "<h2>2. Crear tablas</h2>";
$install_file = dirname(__FILE__) . '/includes/modules/presupuestos-participativos/install.php';
if (file_exists($install_file)) {
    delete_option('flavor_presupuestos_participativos_db_version');
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once $install_file;
    echo "<p class='ok'>✅ Install.php ejecutado</p>";
} else {
    echo "<p class='error'>❌ install.php no encontrado en: {$install_file}</p>";
}

// 3. Verificar tablas de nuevo
echo "<h2>3. Verificar tablas después de install</h2>";
foreach ($tablas as $tabla) {
    $nombre_completo = $wpdb->prefix . $tabla;
    $existe = $wpdb->get_var("SHOW TABLES LIKE '{$nombre_completo}'");
    $estado = $existe ? 'ok' : 'error';
    $texto = $existe ? '✅ Existe' : '❌ NO existe';
    echo "<p class='{$estado}'>{$tabla}: {$texto}</p>";

    if ($existe) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$nombre_completo}");
        echo "<p>&nbsp;&nbsp;&nbsp;→ {$count} registros</p>";
    }
}

// 4. Crear edición si no existe
echo "<h2>4. Datos de prueba</h2>";
$tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
$existe_tabla = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_ediciones}'");

if ($existe_tabla) {
    $ediciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_ediciones}");
    if ($ediciones == 0) {
        $wpdb->insert($tabla_ediciones, [
            'nombre' => 'Presupuestos 2026',
            'anio' => 2026,
            'descripcion' => 'Presupuestos participativos del año 2026',
            'presupuesto_total' => 100000.00,
            'fase' => 'propuestas',
            'estado' => 'activo',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'fecha_inicio_propuestas' => date('Y-m-d'),
            'fecha_fin_propuestas' => date('Y-m-d', strtotime('+60 days')),
            'votos_por_ciudadano' => 3,
        ]);
        echo "<p class='ok'>✅ Edición 2026 creada</p>";
    } else {
        echo "<p class='ok'>✅ Ya hay {$ediciones} ediciones</p>";
    }

    // Mostrar ediciones
    $ediciones_data = $wpdb->get_results("SELECT * FROM {$tabla_ediciones}");
    echo "<pre>" . print_r($ediciones_data, true) . "</pre>";
}

// 5. Crear proyectos de prueba
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
$existe_proyectos = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_proyectos}'");

if ($existe_proyectos) {
    $proyectos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos}");
    if ($proyectos == 0) {
        $edicion_id = $wpdb->get_var("SELECT id FROM {$tabla_ediciones} LIMIT 1");
        if ($edicion_id) {
            $wpdb->insert($tabla_proyectos, [
                'edicion_id' => $edicion_id,
                'proponente_id' => get_current_user_id() ?: 1,
                'titulo' => 'Parque infantil accesible',
                'descripcion' => 'Crear un parque infantil accesible para todos.',
                'categoria' => 'social',
                'presupuesto_solicitado' => 25000.00,
                'estado' => 'validado',
                'votos_recibidos' => 15,
            ]);
            $wpdb->insert($tabla_proyectos, [
                'edicion_id' => $edicion_id,
                'proponente_id' => get_current_user_id() ?: 1,
                'titulo' => 'Carril bici',
                'descripcion' => 'Ampliar el carril bici del barrio.',
                'categoria' => 'infraestructura',
                'presupuesto_solicitado' => 40000.00,
                'estado' => 'validado',
                'votos_recibidos' => 28,
            ]);
            echo "<p class='ok'>✅ 2 proyectos de prueba creados</p>";
        }
    } else {
        echo "<p class='ok'>✅ Ya hay {$proyectos} proyectos</p>";
    }

    // Mostrar columnas
    echo "<h3>Columnas de proyectos:</h3>";
    $cols = $wpdb->get_col("DESCRIBE {$tabla_proyectos}");
    echo "<p>" . implode(', ', $cols) . "</p>";
}

// 6. Verificar shortcodes
echo "<h2>5. Shortcodes registrados</h2>";
$shortcodes_pp = [
    'presupuestos_listado',
    'presupuestos_votar',
    'presupuestos_resultados',
    'presupuestos_mi_proyecto',
    'presupuesto_estado_actual',
    'presupuestos_seguimiento',
];

foreach ($shortcodes_pp as $sc) {
    $existe = shortcode_exists($sc);
    $estado = $existe ? 'ok' : 'error';
    $texto = $existe ? '✅ Registrado' : '❌ NO registrado';
    echo "<p class='{$estado}'>[{$sc}]: {$texto}</p>";
}

// 7. Probar shortcode
echo "<h2>6. Probar shortcode [presupuestos_listado]</h2>";
if (shortcode_exists('presupuestos_listado')) {
    $output = do_shortcode('[presupuestos_listado limite="3"]');
    $len = strlen($output);
    echo "<p>Longitud del output: {$len} caracteres</p>";
    if ($len > 0) {
        echo "<div style='border:1px solid #ccc;padding:10px;margin:10px 0;'>";
        echo $output;
        echo "</div>";
    } else {
        echo "<p class='error'>El shortcode devuelve vacío</p>";
    }
} else {
    echo "<p class='error'>Shortcode no registrado</p>";
}

// 8. Verificar módulo
echo "<h2>7. Estado del módulo</h2>";
if (class_exists('Flavor_Chat_Presupuestos_Participativos_Module')) {
    echo "<p class='ok'>✅ Clase del módulo existe</p>";
} else {
    echo "<p class='error'>❌ Clase del módulo NO existe</p>";
}

// 8. Verificar método get_dashboard_tabs
echo "<h2>8. Método get_dashboard_tabs</h2>";
if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $module = $loader->get_module('presupuestos_participativos');
    if ($module) {
        echo "<p class='ok'>✅ Módulo cargado: " . get_class($module) . "</p>";
        if (method_exists($module, 'get_dashboard_tabs')) {
            $tabs = $module->get_dashboard_tabs();
            echo "<p>get_dashboard_tabs() devuelve: </p><pre>" . print_r($tabs, true) . "</pre>";
        } else {
            echo "<p>❌ No tiene método get_dashboard_tabs() - usará fallback</p>";
        }
    } else {
        echo "<p class='error'>❌ Módulo NO cargado por el loader</p>";
        echo "<p>Módulos disponibles:</p><pre>";
        print_r($loader->get_active_modules());
        echo "</pre>";
    }
} else {
    echo "<p class='error'>❌ Flavor_Chat_Module_Loader no existe</p>";
}

// 9. Verificar Dynamic Pages
echo "<h2>9. Verificar Dynamic Pages</h2>";
if (class_exists('Flavor_Dynamic_Pages')) {
    echo "<p class='ok'>✅ Flavor_Dynamic_Pages existe</p>";
} else {
    echo "<p class='error'>❌ Flavor_Dynamic_Pages NO existe</p>";
}

echo "<h2>✅ Diagnóstico completado</h2>";
echo "<p><a href='" . home_url('/mi-portal/presupuestos-participativos/') . "'>Ir a Presupuestos Participativos</a></p>";
