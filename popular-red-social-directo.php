<?php
/**
 * Script para popular red social directamente
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/popular-red-social-directo.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Popular Red Social</title></head><body>';
echo '<h1>Popular Red Social - Directo</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

global $wpdb;

// 1. Verificar/Crear tablas
echo '<h3>1. Verificar/Crear Tablas</h3>';

$tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';
$tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';

if (!Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
    echo '<p>Las tablas no existen. Creándolas...</p>';

    require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/class-red-social-module.php';

    if (class_exists('Flavor_Chat_Red_Social_Module')) {
        $modulo = new Flavor_Chat_Red_Social_Module();

        if (method_exists($modulo, 'maybe_create_tables')) {
            $modulo->maybe_create_tables();
            echo '<p style="color: green;">✓ maybe_create_tables() ejecutado</p>';

            // Verificar de nuevo
            if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
                echo '<p style="color: green;">✓ Tablas creadas correctamente</p>';
            } else {
                echo '<p style="color: red;">✗ Las tablas no se crearon</p>';
                echo '</div></body></html>';
                exit;
            }
        } else {
            echo '<p style="color: red;">✗ Método maybe_create_tables() no existe</p>';
            echo '</div></body></html>';
            exit;
        }
    } else {
        echo '<p style="color: red;">✗ Clase Flavor_Chat_Red_Social_Module no encontrada</p>';
        echo '</div></body></html>';
        exit;
    }
} else {
    echo '<p style="color: green;">✓ Las tablas ya existen</p>';
}

// 2. Crear usuarios demo
echo '<h3>2. Crear Usuarios Demo</h3>';

$usuarios_demo = [];
for ($i = 1; $i <= 5; $i++) {
    $username = "demo_usuario_{$i}";

    // Verificar si ya existe
    $user = get_user_by('login', $username);

    if (!$user) {
        $user_id = wp_create_user(
            $username,
            'demo123',
            "demo{$i}@example.com"
        );

        if (!is_wp_error($user_id)) {
            update_user_meta($user_id, 'first_name', "Usuario");
            update_user_meta($user_id, 'last_name', "Demo $i");
            update_user_meta($user_id, 'flavor_demo_user', true);
            $usuarios_demo[] = $user_id;
            echo "<p style='color: green;'>✓ Usuario creado: {$username} (ID: {$user_id})</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando usuario: " . $user_id->get_error_message() . "</p>";
        }
    } else {
        $usuarios_demo[] = $user->ID;
        echo "<p style='color: gray;'>⊘ Usuario ya existe: {$username} (ID: {$user->ID})</p>";
    }
}

// 3. Crear perfiles
echo '<h3>3. Crear Perfiles</h3>';

$bios = [
    'Amante de la naturaleza 🌱',
    'Fotógrafo aficionado 📷',
    'Cocinera por pasión 👩‍🍳',
    'Ciclista urbano 🚴‍♂️',
    'Lectora empedernida 📚'
];

$ubicaciones = [
    'Centro histórico',
    'Barrio San Juan',
    'Ensanche',
    'Plaza Mayor',
    'Casco Antiguo'
];

$perfiles_creados = 0;
foreach ($usuarios_demo as $index => $usuario_id) {
    $user = get_userdata($usuario_id);
    if (!$user) continue;

    // Verificar si ya tiene perfil
    $perfil_existe = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_perfiles WHERE usuario_id = %d",
        $usuario_id
    ));

    if (!$perfil_existe) {
        $resultado = $wpdb->insert(
            $tabla_perfiles,
            [
                'usuario_id' => $usuario_id,
                'nombre_completo' => $user->first_name . ' ' . $user->last_name,
                'bio' => $bios[$index],
                'ubicacion' => $ubicaciones[$index],
                'es_verificado' => 0,
                'es_privado' => 0,
                'total_publicaciones' => 0,
                'total_seguidores' => 0,
                'total_siguiendo' => 0,
                'fecha_creacion' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s']
        );

        if ($resultado) {
            $perfiles_creados++;
            echo "<p style='color: green;'>✓ Perfil creado para: {$user->display_name}</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando perfil: " . $wpdb->last_error . "</p>";
        }
    } else {
        echo "<p style='color: gray;'>⊘ Perfil ya existe para: {$user->display_name}</p>";
    }
}

echo "<p><strong>Perfiles creados: {$perfiles_creados}</strong></p>";

// 4. Crear publicaciones
echo '<h3>4. Crear Publicaciones</h3>';

$publicaciones_textos = [
    '¡Hoy cosechamos los primeros tomates del huerto comunitario! 🍅',
    'Atardecer increíble desde el mirador 📸',
    'Nueva receta: paella de verduras ecológicas 🥘',
    'Recordatorio: mañana salida en bici a las 8am 🚴‍♀️',
    'Terminé de leer "El jardín de las mariposas" 📖',
    'Gracias a todos los que vinieron a la asamblea 💪',
    'Cambio plantones de tomate por semillas de albahaca',
    'Nuevo mural terminado en la calle Mayor! 🎨',
    'La biblioteca tiene nuevos libros infantiles 📚',
    'Clase de cocina gratuita este miércoles 🍞'
];

$publicaciones_creadas = 0;
foreach ($publicaciones_textos as $i => $contenido) {
    $autor_id = $usuarios_demo[array_rand($usuarios_demo)];

    $resultado = $wpdb->insert(
        $tabla_publicaciones,
        [
            'autor_id' => $autor_id,
            'contenido' => $contenido,
            'tipo' => 'texto',
            'visibilidad' => 'publica',
            'estado' => 'publicado',
            'me_gusta' => rand(0, 15),
            'comentarios' => 0,
            'compartidos' => 0,
            'vistas' => rand(10, 100),
            'fecha_publicacion' => date('Y-m-d H:i:s', strtotime("-{$i} hours"))
        ],
        ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s']
    );

    if ($resultado) {
        $publicaciones_creadas++;
    }
}

echo "<p style='color: green;'><strong>✓ Publicaciones creadas: {$publicaciones_creadas}</strong></p>";

echo '</div>';

// Resumen
echo '<h2 style="margin: 20px;">Resumen</h2>';
echo '<div style="background: #d4edda; padding: 20px; margin: 20px; border: 1px solid #c3e6cb; border-radius: 4px;">';
echo "<p style='font-size: 16px; margin: 5px 0;'>✓ Usuarios: " . count($usuarios_demo) . "</p>";
echo "<p style='font-size: 16px; margin: 5px 0;'>✓ Perfiles: {$perfiles_creados}</p>";
echo "<p style='font-size: 16px; margin: 5px 0;'>✓ Publicaciones: {$publicaciones_creadas}</p>";
echo '</div>';

echo '<p style="margin: 20px;"><a href="' . admin_url('admin.php?page=flavor-app-composer') . '" class="button button-primary">Volver al Compositor</a></p>';
echo '</body></html>';
