<?php
/**
 * Script temporal para crear datos de demo de Presupuestos Participativos
 * Ejecutar visitando: /wp-content/plugins/flavor-chat-ia/setup-pp-demo.php
 */

// Cargar WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ejecutar este script');
}

global $wpdb;

echo "<h1>Setup Presupuestos Participativos</h1>";

// 1. Crear tablas si no existen
$install_file = dirname(__FILE__) . '/includes/modules/presupuestos-participativos/install.php';
if (file_exists($install_file)) {
    // Forzar reinstalación
    delete_option('flavor_presupuestos_participativos_db_version');
    require_once $install_file;
    echo "<p>✅ Tablas creadas/actualizadas</p>";
} else {
    echo "<p>❌ No se encontró install.php</p>";
}

// 2. Verificar tablas
$tablas = [
    'flavor_pp_ediciones',
    'flavor_pp_proyectos',
    'flavor_pp_votos',
    'flavor_pp_categorias'
];

echo "<h2>Tablas:</h2><ul>";
foreach ($tablas as $tabla) {
    $existe = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$tabla}'");
    echo "<li>{$tabla}: " . ($existe ? '✅ Existe' : '❌ No existe') . "</li>";
}
echo "</ul>";

// 3. Crear edición de prueba si no existe
$tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
$ediciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_ediciones}");

if ($ediciones == 0) {
    $result = $wpdb->insert($tabla_ediciones, [
        'nombre' => 'Presupuestos Participativos 2026',
        'anio' => 2026,
        'descripcion' => 'Decide en qué invertir el presupuesto del barrio para este año.',
        'presupuesto_total' => 100000.00,
        'fase' => 'propuestas',
        'estado' => 'activo',
        'fecha_inicio' => date('Y-01-01'),
        'fecha_fin' => date('Y-12-31'),
        'fecha_inicio_propuestas' => date('Y-m-d'),
        'fecha_fin_propuestas' => date('Y-m-d', strtotime('+60 days')),
        'votos_por_ciudadano' => 3,
    ]);

    if ($result) {
        echo "<p>✅ Edición 2026 creada</p>";
    } else {
        echo "<p>❌ Error al crear edición: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p>✅ Ya existen {$ediciones} ediciones</p>";
}

// 4. Crear proyecto de ejemplo si no existe
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
$proyectos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos}");

if ($proyectos == 0) {
    $edicion_id = $wpdb->get_var("SELECT id FROM {$tabla_ediciones} ORDER BY anio DESC LIMIT 1");

    $proyectos_demo = [
        [
            'titulo' => 'Parque infantil accesible',
            'descripcion' => 'Crear un parque infantil completamente accesible para niños con diversidad funcional.',
            'categoria' => 'accesibilidad',
            'presupuesto_solicitado' => 25000.00,
            'ubicacion' => 'Plaza Mayor',
        ],
        [
            'titulo' => 'Carril bici conectado',
            'descripcion' => 'Ampliar el carril bici para conectar el barrio con el centro de la ciudad.',
            'categoria' => 'infraestructura',
            'presupuesto_solicitado' => 45000.00,
            'ubicacion' => 'Avenida Principal',
        ],
        [
            'titulo' => 'Huerto comunitario',
            'descripcion' => 'Crear un espacio de huertos urbanos para vecinos del barrio.',
            'categoria' => 'medio_ambiente',
            'presupuesto_solicitado' => 15000.00,
            'ubicacion' => 'Solar municipal calle Verde',
        ],
    ];

    foreach ($proyectos_demo as $proyecto) {
        $wpdb->insert($tabla_proyectos, array_merge($proyecto, [
            'edicion_id' => $edicion_id,
            'proponente_id' => get_current_user_id(),
            'estado' => 'validado',
            'votos_recibidos' => rand(5, 50),
            'ranking' => 0,
            'fecha_creacion' => current_time('mysql'),
        ]));
    }

    echo "<p>✅ 3 proyectos de demo creados</p>";
} else {
    echo "<p>✅ Ya existen {$proyectos} proyectos</p>";
}

// 5. Verificar estructura de proyectos
echo "<h2>Columnas de {$tabla_proyectos}:</h2><ul>";
$columnas = $wpdb->get_col("DESCRIBE {$tabla_proyectos}");
foreach ($columnas as $col) {
    echo "<li>{$col}</li>";
}
echo "</ul>";

echo "<h2>✅ Setup completado</h2>";
echo "<p><a href='" . home_url('/mi-portal/presupuestos-participativos/') . "'>Ir a Presupuestos Participativos</a></p>";

// Eliminar este archivo después de usar
// unlink(__FILE__);
