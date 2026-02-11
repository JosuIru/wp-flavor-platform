<?php
/**
 * Script para poblar el sistema completo con datos demo
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/poblar-sistema-completo.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Poblar Sistema Completo</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
h1 { color: #1d2327; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
h2 { color: #2271b1; margin-top: 30px; }
.seccion { background: #f6f7f7; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1; }
.exito { color: #00a32a; font-weight: 500; }
.error { color: #d63638; font-weight: 500; }
.info { color: #2271b1; }
.contador { background: #2271b1; color: white; padding: 10px 20px; border-radius: 4px; display: inline-block; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f6f7f7; font-weight: 600; }
.btn { display: inline-block; background: #2271b1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
.btn:hover { background: #135e96; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>🚀 Poblar Sistema Completo con Datos Demo</h1>';

global $wpdb;
$resultados = [
    'usuarios' => 0,
    'tablas_creadas' => 0,
    'modulos_poblados' => 0,
    'modulos_sin_soporte' => 0,
    'errores' => []
];

// ========================================
// 1. CREAR USUARIOS COMPARTIDOS
// ========================================
echo '<div class="seccion">';
echo '<h2>👥 Paso 1: Crear Usuarios Demo Compartidos</h2>';

$usuarios_demo = [
    ['nombre' => 'María García López', 'email' => 'demo-maria@example.com', 'telefono' => '611222333', 'direccion' => 'C/ San Pedro 15, 2º A'],
    ['nombre' => 'Juan Martínez Ruiz', 'email' => 'demo-juan@example.com', 'telefono' => '622333444', 'direccion' => 'Av. Navarra 45, 3º B'],
    ['nombre' => 'Ana Sánchez Torres', 'email' => 'demo-ana@example.com', 'telefono' => '633444555', 'direccion' => 'C/ Mayor 8, 1º'],
    ['nombre' => 'Carlos Fernández Gil', 'email' => 'demo-carlos@example.com', 'telefono' => '644555666', 'direccion' => 'Plaza del Castillo 12'],
    ['nombre' => 'Laura Pérez Muñoz', 'email' => 'demo-laura@example.com', 'telefono' => '655666777', 'direccion' => 'C/ Estafeta 22, 4º D'],
    ['nombre' => 'Pedro González Vega', 'email' => 'demo-pedro@example.com', 'telefono' => '666777888', 'direccion' => 'C/ Comercio 33, bajo'],
    ['nombre' => 'Isabel Torres Mora', 'email' => 'demo-isabel@example.com', 'telefono' => '677888999', 'direccion' => 'Av. Libertad 18, 5º C'],
    ['nombre' => 'David Gil Romero', 'email' => 'demo-david@example.com', 'telefono' => '688999000', 'direccion' => 'Plaza Nueva 5, 2º'],
];

$user_ids = [];

foreach ($usuarios_demo as $usuario_data) {
    $existing = get_user_by('email', $usuario_data['email']);

    if ($existing) {
        $user_ids[] = $existing->ID;
        echo "<p class='info'>⊘ Usuario ya existe: {$usuario_data['nombre']} (ID: {$existing->ID})</p>";
        continue;
    }

    $usuario_login = sanitize_user(str_replace(' ', '_', strtolower($usuario_data['nombre'])));
    $usuario_id = wp_create_user($usuario_login, wp_generate_password(12, false), $usuario_data['email']);

    if (!is_wp_error($usuario_id)) {
        $user_ids[] = $usuario_id;
        $resultados['usuarios']++;

        // Marcar como usuario demo
        update_user_meta($usuario_id, '_flavor_demo_data', '1');

        // Datos personales
        $nombres = explode(' ', $usuario_data['nombre']);
        update_user_meta($usuario_id, 'first_name', $nombres[0]);
        update_user_meta($usuario_id, 'last_name', trim(str_replace($nombres[0], '', $usuario_data['nombre'])));

        // Datos de contacto
        update_user_meta($usuario_id, 'billing_phone', $usuario_data['telefono']);
        update_user_meta($usuario_id, 'billing_address_1', $usuario_data['direccion']);
        update_user_meta($usuario_id, 'telefono', $usuario_data['telefono']);
        update_user_meta($usuario_id, 'direccion', $usuario_data['direccion']);

        // Asignar rol
        $user = new WP_User($usuario_id);
        $user->set_role('subscriber');

        echo "<p class='exito'>✓ Usuario creado: {$usuario_data['nombre']} (ID: {$usuario_id})</p>";
    } else {
        echo "<p class='error'>✗ Error creando usuario: " . $usuario_id->get_error_message() . "</p>";
        $resultados['errores'][] = "Usuario {$usuario_data['nombre']}: " . $usuario_id->get_error_message();
    }
}

echo "<div class='contador'>Usuarios totales: " . count($user_ids) . "</div>";
echo '</div>';

// ========================================
// 2. USAR DEMO DATA MANAGER
// ========================================
echo '<div class="seccion">';
echo '<h2>📦 Paso 2: Poblar Módulos con Demo Data Manager</h2>';

require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-manager.php';
$manager = Flavor_Demo_Data_Manager::get_instance();

// Obtener módulos activos
$settings = get_option('flavor_chat_ia_settings', []);
$modulos_activos = $settings['active_modules'] ?? [];

if (empty($modulos_activos)) {
    echo "<p class='info'>ℹ No hay módulos activos configurados</p>";
} else {
    echo "<p class='info'>Módulos activos: " . count($modulos_activos) . "</p>";

    // Poblar cada módulo
    foreach ($modulos_activos as $modulo_id) {
        echo "<h3>Módulo: {$modulo_id}</h3>";

        $resultado = $manager->populate_module($modulo_id);

        if (isset($resultado['success']) && $resultado['success']) {
            echo "<p class='exito'>✓ Poblado exitosamente</p>";
            $resultados['modulos_poblados']++;

            if (isset($resultado['counts'])) {
                echo "<ul>";
                foreach ($resultado['counts'] as $tipo => $cantidad) {
                    echo "<li>{$tipo}: <strong>{$cantidad}</strong></li>";
                }
                echo "</ul>";
            } elseif (isset($resultado['count'])) {
                echo "<p>Registros creados: <strong>{$resultado['count']}</strong></p>";
            }
        } else {
            $error_msg = $resultado['error'] ?? 'Error desconocido';

            // Distinguir entre módulo sin soporte y error real
            if ($error_msg === 'Módulo no soportado') {
                echo "<p class='info'>ℹ Sin método populate_{$modulo_id}() - Omitido</p>";
                $resultados['modulos_sin_soporte']++;
            } else {
                echo "<p class='error'>✗ Error: {$error_msg}</p>";
                $resultados['errores'][] = "{$modulo_id}: {$error_msg}";
            }
        }
    }
}

echo '</div>';

// ========================================
// 3. VERIFICAR DATOS CREADOS
// ========================================
echo '<div class="seccion">';
echo '<h2>✅ Paso 3: Verificación de Datos</h2>';

$verificaciones = [
    'Socios' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_socios",
    'Empleados' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_empleados",
    'Eventos' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos WHERE es_demo = 1",
    'Facturas' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_facturas WHERE es_demo = 1",
    'Red Social Perfiles' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_perfiles",
    'Red Social Publicaciones' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_publicaciones",
    'Marketplace Productos' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_marketplace_productos WHERE es_demo = 1",
    'Reservas' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_reservas WHERE es_demo = 1",
    'Incidencias' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_incidencias WHERE es_demo = 1",
    'Talleres' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_talleres WHERE es_demo = 1",
];

echo '<table>';
echo '<tr><th>Tabla/Módulo</th><th>Registros</th></tr>';

foreach ($verificaciones as $nombre => $query) {
    $count = $wpdb->get_var($query);
    $count = $count !== null ? $count : 0;
    $clase = $count > 0 ? 'exito' : 'error';
    echo "<tr><td>{$nombre}</td><td class='{$clase}'>{$count}</td></tr>";
}

echo '</table>';
echo '</div>';

// ========================================
// RESUMEN FINAL
// ========================================
echo '<div class="seccion" style="background: #d4edda; border-color: #00a32a;">';
echo '<h2 style="color: #00a32a;">📊 Resumen Final</h2>';
echo "<p><strong>Usuarios creados/verificados:</strong> " . count($user_ids) . "</p>";
echo "<p><strong>Módulos poblados exitosamente:</strong> {$resultados['modulos_poblados']}</p>";
echo "<p><strong>Módulos sin soporte:</strong> {$resultados['modulos_sin_soporte']} (normal, no tienen método populate_*)</p>";

if (!empty($resultados['errores'])) {
    echo '<h3 style="color: #d63638;">❌ Errores Reales:</h3>';
    echo '<ul>';
    foreach ($resultados['errores'] as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo '</ul>';
} else {
    echo "<p class='exito' style='font-size: 18px;'>✓ ¡Todos los módulos con soporte se poblaron correctamente!</p>";
}

echo '</div>';

echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '" class="btn">← Volver al Dashboard</a></p>';
echo '</div>';
echo '</body></html>';
