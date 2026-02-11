<?php
/**
 * Script para verificar y crear tablas de módulos
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/verificar-tablas.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Verificación y Creación de Tablas de Módulos</h1>';

global $wpdb;

// Obtener todas las tablas existentes
$tablas_existentes = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}flavor_%'");

echo '<h2>Tablas Flavor existentes (' . count($tablas_existentes) . '):</h2>';
if (count($tablas_existentes) > 0) {
    echo '<ul>';
    foreach ($tablas_existentes as $tabla) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `$tabla`");
        echo "<li><strong>{$tabla}</strong>: {$count} registros</li>";
    }
    echo '</ul>';
} else {
    echo '<p style="color: red;">⚠ No hay tablas flavor creadas</p>';
}

echo '<hr>';
echo '<h2>Crear tablas faltantes:</h2>';

// Obtener configuración
$settings = get_option('flavor_chat_ia_settings', []);
$modulos_activos = $settings['active_modules'] ?? [];

echo '<p>Módulos activos: ' . count($modulos_activos) . '</p>';
echo '<ul>';
foreach ($modulos_activos as $modulo_id) {
    echo "<li>{$modulo_id}</li>";
}
echo '</ul>';

// Botón para crear tablas
echo '<hr>';
echo '<h2>Acciones:</h2>';
echo '<form method="post" style="margin: 20px 0;">';
echo '<input type="hidden" name="action" value="crear_tablas">';
wp_nonce_field('crear_tablas_flavor', 'crear_tablas_nonce');
echo '<button type="submit" class="button button-primary button-large">Crear todas las tablas de módulos activos</button>';
echo '</form>';

// Procesar creación de tablas
if (isset($_POST['action']) && $_POST['action'] === 'crear_tablas') {
    check_admin_referer('crear_tablas_flavor', 'crear_tablas_nonce');

    echo '<div style="background: #fff; border-left: 4px solid #46b450; padding: 12px; margin: 20px 0;">';
    echo '<h3>Creando tablas...</h3>';

    // Cargar el instalador de tablas
    require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/components/class-table-installer.php';

    if (class_exists('Flavor_Table_Installer')) {
        $installer = new Flavor_Table_Installer();

        // Usar el método instalar() que maneja internamente la creación
        $resultado = $installer->instalar('manual', [
            'modulos' => $modulos_activos
        ]);

        if ($resultado['success']) {
            echo '<p style="color: green;">✓ Proceso completado exitosamente</p>';

            if (!empty($resultado['data']['tablas_creadas'])) {
                echo '<h4>Tablas creadas:</h4><ul>';
                foreach ($resultado['data']['tablas_creadas'] as $tabla) {
                    echo "<li style='color: green;'>✓ {$tabla}</li>";
                }
                echo '</ul>';
            }

            if (!empty($resultado['data']['tablas_existentes'])) {
                echo '<h4>Tablas que ya existían:</h4><ul>';
                foreach ($resultado['data']['tablas_existentes'] as $tabla) {
                    echo "<li style='color: orange;'>⚠ {$tabla}</li>";
                }
                echo '</ul>';
            }

            if (!empty($resultado['data']['tablas_fallidas'])) {
                echo '<h4>Errores:</h4><ul>';
                foreach ($resultado['data']['tablas_fallidas'] as $modulo => $error) {
                    echo "<li style='color: red;'>✗ {$modulo}: {$error}</li>";
                }
                echo '</ul>';
            }
        } else {
            echo '<p style="color: red;">✗ Error: ' . $resultado['message'] . '</p>';
        }
    } else {
        echo '<p style="color: red;">✗ Clase Flavor_Table_Installer no encontrada</p>';
    }

    echo '</div>';

    // Recargar tablas existentes
    $tablas_existentes = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}flavor_%'");
    echo '<h3>Tablas después de crear (' . count($tablas_existentes) . '):</h3>';
    echo '<ul>';
    foreach ($tablas_existentes as $tabla) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `$tabla`");
        echo "<li><strong>{$tabla}</strong>: {$count} registros</li>";
    }
    echo '</ul>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">← Volver al Compositor</a></p>';
