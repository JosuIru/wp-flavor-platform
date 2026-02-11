<?php
/**
 * Script de diagnóstico del sistema de datos demo
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/diagnostico-datos-demo.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Diagnóstico Datos Demo</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
h1 { color: #1d2327; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
h2 { color: #2271b1; margin-top: 30px; }
.seccion { background: #f6f7f7; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1; }
.ok { color: #00a32a; font-weight: 500; }
.error { color: #d63638; font-weight: 500; }
.warning { color: #dba617; font-weight: 500; }
.info { color: #2271b1; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f6f7f7; font-weight: 600; }
.badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-ok { background: #d4edda; color: #00a32a; }
.badge-error { background: #f8d7da; color: #d63638; }
.badge-warning { background: #fff3cd; color: #856404; }
.accion { background: #2271b1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 10px 10px 0; display: inline-block; }
.accion:hover { background: #135e96; color: white; }
.alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
.alert-success { background: #d4edda; border-left: 4px solid #00a32a; color: #155724; }
.alert-danger { background: #f8d7da; border-left: 4px solid #d63638; color: #721c24; }
.alert-warning { background: #fff3cd; border-left: 4px solid #dba617; color: #856404; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>🔍 Diagnóstico del Sistema de Datos Demo</h1>';

global $wpdb;

// ========================================
// 1. VERIFICAR USUARIOS DEMO
// ========================================
echo '<div class="seccion">';
echo '<h2>👥 Usuarios Demo Compartidos</h2>';

$usuarios_demo = get_users([
    'meta_key' => '_flavor_demo_data',
    'meta_value' => '1',
]);

if (count($usuarios_demo) >= 8) {
    echo "<div class='alert alert-success'>";
    echo "✓ Sistema configurado correctamente con <strong>" . count($usuarios_demo) . " usuarios demo</strong>";
    echo "</div>";

    echo "<table>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    foreach ($usuarios_demo as $user) {
        echo "<tr>";
        echo "<td>{$user->ID}</td>";
        echo "<td>{$user->display_name}</td>";
        echo "<td>{$user->user_email}</td>";
        echo "<td>" . implode(', ', $user->roles) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "⚠ Solo hay <strong>" . count($usuarios_demo) . " usuarios demo</strong>. Se necesitan 8 usuarios compartidos.";
    echo "</div>";
    echo "<p>👉 Solución: Ejecuta el script de población completa.</p>";
}

echo '</div>';

// ========================================
// 2. VERIFICAR MÓDULOS ACTIVOS
// ========================================
echo '<div class="seccion">';
echo '<h2>📦 Módulos Activos</h2>';

$settings = get_option('flavor_chat_ia_settings', []);
$modulos_activos = $settings['active_modules'] ?? [];

if (empty($modulos_activos)) {
    echo "<div class='alert alert-danger'>";
    echo "✗ No hay módulos activos configurados";
    echo "</div>";
} else {
    echo "<p class='info'>Total de módulos activos: <strong>" . count($modulos_activos) . "</strong></p>";

    // Verificar qué módulos tienen métodos populate_*
    require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-manager.php';
    $manager = Flavor_Demo_Data_Manager::get_instance();

    echo "<table>";
    echo "<tr><th>Módulo</th><th>Tiene populate_()</th><th>Estado Datos</th></tr>";

    foreach ($modulos_activos as $modulo_id) {
        $metodo_existe = method_exists($manager, 'populate_' . str_replace('-', '_', $modulo_id));
        $tiene_datos = $manager->has_demo_data($modulo_id);
        $count = $manager->get_demo_data_count($modulo_id);

        echo "<tr>";
        echo "<td>{$modulo_id}</td>";
        echo "<td>";
        if ($metodo_existe) {
            echo "<span class='badge badge-ok'>✓ SÍ</span>";
        } else {
            echo "<span class='badge badge-error'>✗ NO</span>";
        }
        echo "</td>";
        echo "<td>";
        if ($tiene_datos) {
            echo "<span class='badge badge-ok'>{$count} registros</span>";
        } else {
            echo "<span class='badge badge-error'>Sin datos</span>";
        }
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo '</div>';

// ========================================
// 3. VERIFICAR TABLAS CRÍTICAS
// ========================================
echo '<div class="seccion">';
echo '<h2>🗄️ Tablas de Base de Datos</h2>';

$tablas_criticas = [
    'Empleados' => 'flavor_empleados',
    'Fichajes' => 'flavor_fichajes',
    'Socios' => 'flavor_socios',
    'Eventos' => 'flavor_eventos',
    'Facturas' => 'flavor_facturas',
    'Red Social Perfiles' => 'flavor_social_perfiles',
    'Red Social Publicaciones' => 'flavor_social_publicaciones',
    'Red Social Comentarios' => 'flavor_social_comentarios',
    'Marketplace Productos' => 'flavor_marketplace_productos',
    'Reservas' => 'flavor_reservas',
    'Incidencias' => 'flavor_incidencias',
    'Talleres' => 'flavor_talleres',
    'Avisos Municipales' => 'flavor_avisos_municipales',
    'Trámites' => 'flavor_tramites',
];

echo "<table>";
echo "<tr><th>Tabla</th><th>Existe</th><th>Registros</th></tr>";

$tablas_faltantes = [];
foreach ($tablas_criticas as $nombre => $tabla) {
    $tabla_completa = $wpdb->prefix . $tabla;
    $existe = Flavor_Chat_Helpers::tabla_existe($tabla_completa);

    echo "<tr>";
    echo "<td>{$nombre}</td>";

    if ($existe) {
        echo "<td><span class='badge badge-ok'>✓ Existe</span></td>";
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$tabla_completa}`");
        echo "<td>{$count}</td>";
    } else {
        echo "<td><span class='badge badge-error'>✗ No existe</span></td>";
        echo "<td>-</td>";
        $tablas_faltantes[] = $nombre;
    }

    echo "</tr>";
}

echo "</table>";

if (!empty($tablas_faltantes)) {
    echo "<div class='alert alert-warning'>";
    echo "⚠ Faltan " . count($tablas_faltantes) . " tablas: " . implode(', ', $tablas_faltantes);
    echo "</div>";
    echo "<p>👉 Solución: Las tablas se crearán automáticamente al ejecutar el script de población.</p>";
}

echo '</div>';

// ========================================
// 4. RESUMEN Y ACCIONES
// ========================================
echo '<div class="seccion">';
echo '<h2>📊 Resumen y Acciones Recomendadas</h2>';

$problemas = [];

if (count($usuarios_demo) < 8) {
    $problemas[] = "Faltan usuarios demo compartidos";
}

if (!empty($tablas_faltantes)) {
    $problemas[] = "Faltan " . count($tablas_faltantes) . " tablas en la base de datos";
}

$modulos_sin_datos = 0;
foreach ($modulos_activos as $modulo_id) {
    if (!$manager->has_demo_data($modulo_id)) {
        $modulos_sin_datos++;
    }
}

if ($modulos_sin_datos > 0) {
    $problemas[] = "{$modulos_sin_datos} módulos sin datos demo";
}

if (empty($problemas)) {
    echo "<div class='alert alert-success'>";
    echo "<h3 style='margin-top: 0;'>✓ Sistema Funcionando Correctamente</h3>";
    echo "<p>Todos los usuarios demo están creados y los módulos tienen datos.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h3 style='margin-top: 0;'>⚠ Se encontraron problemas:</h3>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li>{$problema}</li>";
    }
    echo "</ul>";
    echo "</div>";

    echo "<h3>🔧 Soluciones:</h3>";
    echo "<p><strong>Opción 1 (Recomendada):</strong> Ejecuta el script de población completa que creará todo automáticamente:</p>";
    echo "<p><a href='" . plugins_url('poblar-sistema-completo.php', __FILE__) . "' class='accion'>🚀 Poblar Sistema Completo</a></p>";

    echo "<p><strong>Opción 2:</strong> Usa el dashboard para poblar módulos individuales:</p>";
    echo "<p><a href='" . admin_url('admin.php?page=flavor-app-composer') . "' class='accion'>📱 Ir al Dashboard</a></p>";

    if (!empty($tablas_faltantes)) {
        echo "<p><strong>Opción 3:</strong> Solo crear las tablas de Red Social (si el problema está ahí):</p>";
        echo "<p><a href='" . plugins_url('crear-tablas-red-social-sql.php', __FILE__) . "' class='accion'>🗄️ Crear Tablas Red Social</a></p>";
    }
}

echo '</div>';

echo '<p style="margin-top: 30px;"><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">← Volver al Dashboard</a></p>';
echo '</div>';
echo '</body></html>';
