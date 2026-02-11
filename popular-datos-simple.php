<?php
/**
 * Script simple para popular datos de prueba
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/popular-datos-simple.php
 */

// Evitar timeout
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Popular Datos</title></head><body>';
echo '<h1>Popular Datos de Prueba - Simple</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

global $wpdb;

// Contador
$total_creados = 0;

try {
    // 1. RED SOCIAL
    echo '<h3>1. Red Social</h3>';
    $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';
    $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
        // Crear 5 perfiles
        $perfiles = [];
        for ($i = 1; $i <= 5; $i++) {
            $wpdb->insert($tabla_perfiles, [
                'usuario_id' => $i,
                'nombre_completo' => "Usuario Demo $i",
                'bio' => "Biografía del usuario demo $i",
                'ubicacion' => "Madrid, España",
                'fecha_creacion' => current_time('mysql')
            ]);
            $perfiles[] = $wpdb->insert_id;
        }
        echo "<p style='color: green;'>✓ Creados " . count($perfiles) . " perfiles</p>";
        $total_creados += count($perfiles);

        // Crear 10 publicaciones
        if (Flavor_Chat_Helpers::tabla_existe($tabla_publicaciones)) {
            for ($i = 0; $i < 10; $i++) {
                $wpdb->insert($tabla_publicaciones, [
                    'autor_id' => $perfiles[array_rand($perfiles)],
                    'contenido' => "Publicación de prueba #$i con contenido interesante",
                    'tipo' => 'texto',
                    'visibilidad' => 'publica',
                    'estado' => 'publicado',
                    'fecha_publicacion' => date('Y-m-d H:i:s', strtotime("-$i hours"))
                ]);
            }
            echo "<p style='color: green;'>✓ Creadas 10 publicaciones</p>";
            $total_creados += 10;
        }
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de perfiles no existe</p>";
    }

    // 2. EVENTOS
    echo '<h3>2. Eventos</h3>';
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
        for ($i = 1; $i <= 5; $i++) {
            $fecha = date('Y-m-d H:i:s', strtotime("+$i days"));
            $wpdb->insert($tabla_eventos, [
                'titulo' => "Evento Demo $i",
                'descripcion' => "Descripción del evento demo $i",
                'fecha_inicio' => $fecha,
                'fecha_fin' => date('Y-m-d H:i:s', strtotime("+$i days +2 hours")),
                'ubicacion' => "Lugar Demo $i",
                'organizador_id' => 1,
                'estado' => 'publicado',
                'plazas_totales' => 50,
                'plazas_disponibles' => 45,
                'fecha_creacion' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creados 5 eventos</p>";
        $total_creados += 5;
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de eventos no existe</p>";
    }

    // 3. BIBLIOTECA
    echo '<h3>3. Biblioteca</h3>';
    $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_libros)) {
        $libros = [
            ['titulo' => 'El Quijote', 'autor' => 'Miguel de Cervantes'],
            ['titulo' => 'Cien años de soledad', 'autor' => 'Gabriel García Márquez'],
            ['titulo' => '1984', 'autor' => 'George Orwell'],
            ['titulo' => 'El principito', 'autor' => 'Antoine de Saint-Exupéry'],
            ['titulo' => 'Rayuela', 'autor' => 'Julio Cortázar']
        ];

        foreach ($libros as $libro) {
            $wpdb->insert($tabla_libros, [
                'titulo' => $libro['titulo'],
                'autor' => $libro['autor'],
                'propietario_id' => 1,
                'estado' => 'disponible',
                'genero' => 'Ficción',
                'fecha_registro' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creados " . count($libros) . " libros</p>";
        $total_creados += count($libros);
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de libros no existe</p>";
    }

    // 4. INCIDENCIAS
    echo '<h3>4. Incidencias</h3>';
    $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
        $categorias = ['alumbrado', 'limpieza', 'mobiliario_urbano', 'parques'];

        for ($i = 1; $i <= 8; $i++) {
            $estado = $i <= 3 ? 'pendiente' : ($i <= 6 ? 'en_proceso' : 'resuelta');
            $wpdb->insert($tabla_incidencias, [
                'titulo' => "Incidencia Demo $i",
                'descripcion' => "Descripción de la incidencia demo $i",
                'categoria' => $categorias[array_rand($categorias)],
                'usuario_id' => 1,
                'estado' => $estado,
                'prioridad' => 'media',
                'ubicacion' => "Calle Demo $i",
                'fecha_reporte' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creadas 8 incidencias</p>";
        $total_creados += 8;
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de incidencias no existe</p>";
    }

    // 5. TALLERES
    echo '<h3>5. Talleres</h3>';
    $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_talleres)) {
        for ($i = 1; $i <= 4; $i++) {
            $wpdb->insert($tabla_talleres, [
                'titulo' => "Taller Demo $i",
                'descripcion' => "Descripción del taller demo $i",
                'instructor_id' => 1,
                'fecha_inicio' => date('Y-m-d H:i:s', strtotime("+$i weeks")),
                'duracion_horas' => 2,
                'plazas_totales' => 20,
                'plazas_disponibles' => 15,
                'estado' => 'abierto',
                'fecha_creacion' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creados 4 talleres</p>";
        $total_creados += 4;
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de talleres no existe</p>";
    }

    // 6. AVISOS MUNICIPALES
    echo '<h3>6. Avisos Municipales</h3>';
    $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_avisos)) {
        for ($i = 1; $i <= 3; $i++) {
            $wpdb->insert($tabla_avisos, [
                'titulo' => "Aviso Demo $i",
                'contenido' => "Contenido del aviso demo $i",
                'prioridad' => 'media',
                'estado' => 'publicado',
                'autor_id' => 1,
                'fecha_publicacion' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creados 3 avisos</p>";
        $total_creados += 3;
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de avisos no existe</p>";
    }

    // 7. SOCIOS
    echo '<h3>7. Socios</h3>';
    $tabla_socios = $wpdb->prefix . 'flavor_socios';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
        for ($i = 1; $i <= 6; $i++) {
            $wpdb->insert($tabla_socios, [
                'nombre' => "Socio Demo $i",
                'email' => "socio$i@demo.com",
                'tipo_socio' => 'individual',
                'estado' => 'activo',
                'fecha_alta' => current_time('mysql')
            ]);
        }
        echo "<p style='color: green;'>✓ Creados 6 socios</p>";
        $total_creados += 6;
    } else {
        echo "<p style='color: gray;'>⊘ Tabla de socios no existe</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . esc_html($e->getMessage()) . "</p>";
}

echo '</div>';

echo '<h2 style="margin: 20px;">Resumen</h2>';
echo '<div style="background: #d4edda; padding: 20px; margin: 20px; border: 1px solid #c3e6cb; border-radius: 4px;">';
echo "<p style='font-size: 18px; margin: 0;'><strong>Total de registros creados: {$total_creados}</strong></p>";
echo '</div>';

echo '<p style="margin: 20px;"><a href="' . admin_url('admin.php?page=flavor-unified-dashboard') . '" class="button button-primary">Ver Dashboard</a></p>';
echo '</body></html>';
