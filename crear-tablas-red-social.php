<?php
/**
 * Script para crear tablas de red social
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/crear-tablas-red-social.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Crear Tablas de Red Social</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

// Cargar módulo
require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/red-social/class-red-social-module.php';

if (!class_exists('Flavor_Chat_Red_Social_Module')) {
    echo '<p style="color: red;">✗ Clase Flavor_Chat_Red_Social_Module no encontrada</p>';
    echo '</div>';
    exit;
}

try {
    // Crear instancia
    $modulo = new Flavor_Chat_Red_Social_Module();

    echo '<p>Instancia de módulo creada correctamente</p>';

    // Verificar que tenga get_table_schema
    if (!method_exists($modulo, 'get_table_schema')) {
        echo '<p style="color: red;">✗ Método get_table_schema() no existe</p>';
        echo '</div>';
        exit;
    }

    echo '<p>Método get_table_schema() encontrado</p>';

    // Obtener esquemas
    $esquemas = $modulo->get_table_schema();

    if (empty($esquemas)) {
        echo '<p style="color: red;">✗ get_table_schema() devolvió array vacío</p>';
        echo '</div>';
        exit;
    }

    echo '<p>Esquemas obtenidos: ' . count($esquemas) . ' tablas</p>';
    echo '<ul>';
    foreach (array_keys($esquemas) as $tabla) {
        echo "<li>$tabla</li>";
    }
    echo '</ul>';

    // Crear tablas
    echo '<h3>Creando tablas...</h3>';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $creadas = 0;
    foreach ($esquemas as $nombre_tabla => $sql) {
        echo "<p>Procesando: $nombre_tabla</p>";
        $resultado = dbDelta($sql);

        if (!empty($resultado)) {
            echo "<p style='color: green; margin-left: 20px;'>✓ $nombre_tabla creada/actualizada</p>";
            $creadas++;
        } else {
            echo "<p style='color: orange; margin-left: 20px;'>⊘ $nombre_tabla sin cambios</p>";
        }
    }

    echo '<hr>';
    echo "<h3 style='color: green;'>✓ Proceso completado</h3>";
    echo "<p>Tablas procesadas: $creadas</p>";

    // Verificar existencia
    echo '<h3>Verificación de tablas:</h3>';
    echo '<ul>';
    foreach (array_keys($esquemas) as $tabla) {
        $existe = Flavor_Chat_Helpers::tabla_existe($tabla);
        if ($existe) {
            echo "<li style='color: green;'>✓ $tabla existe</li>";
        } else {
            echo "<li style='color: red;'>✗ $tabla NO existe</li>";
        }
    }
    echo '</ul>';

} catch (Exception $e) {
    echo '<p style="color: red;">✗ Error: ' . esc_html($e->getMessage()) . '</p>';
}

echo '</div>';

echo '<hr>';
echo '<p><a href="' . plugins_url('popular-datos-simple.php', __FILE__) . '" class="button button-primary">Popular Datos →</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-unified-dashboard') . '" class="button">Ver Dashboard</a></p>';
