<?php
/**
 * Script para recrear tablas de módulos con esquemas faltantes
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/recrear-tablas-faltantes.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar que el usuario es administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<h1>Recrear Tablas con Esquemas Corregidos</h1>';

global $wpdb;

// Módulos que necesitan recrear tablas
$modulos_a_recrear = [
    'trading_ia',
    'biblioteca',
    'avisos_municipales',
    'tramites',
    'carpooling'
];

echo '<h2>Módulos a recrear:</h2>';
echo '<ul>';
foreach ($modulos_a_recrear as $modulo) {
    echo "<li>{$modulo}</li>";
}
echo '</ul>';

// Botón para ejecutar
echo '<hr>';
echo '<form method="post" style="margin: 20px 0;">';
echo '<input type="hidden" name="action" value="recrear_tablas">';
wp_nonce_field('recrear_tablas_flavor', 'recrear_tablas_nonce');
echo '<button type="submit" class="button button-primary button-large" style="background: #dc2626;">Eliminar y Recrear Tablas</button>';
echo '<p style="color: #dc2626;"><strong>⚠ ADVERTENCIA:</strong> Esto eliminará los datos existentes en estas tablas.</p>';
echo '</form>';

// Procesar recreación
if (isset($_POST['action']) && $_POST['action'] === 'recrear_tablas') {
    check_admin_referer('recrear_tablas_flavor', 'recrear_tablas_nonce');

    echo '<div style="background: #fff; border-left: 4px solid #2563eb; padding: 12px; margin: 20px 0;">';
    echo '<h3>Proceso de Recreación</h3>';

    // 1. Obtener todas las tablas de estos módulos
    $prefijo = $wpdb->prefix . 'flavor_';
    $patrones = [
        'trading_ia_%',
        'biblioteca_%',
        'avisos_%',
        'expedientes',
        'tipos_tramite',
        'documentos_expediente',
        'estados_tramite',
        'campos_formulario',
        'historial_expediente',
        'carpooling_%'
    ];

    $tablas_a_eliminar = [];
    foreach ($patrones as $patron) {
        $tablas = $wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s", $prefijo . $patron));
        $tablas_a_eliminar = array_merge($tablas_a_eliminar, $tablas);
    }

    echo '<h4>Paso 1: Eliminando tablas existentes</h4>';
    echo '<ul>';
    foreach ($tablas_a_eliminar as $tabla) {
        $resultado = $wpdb->query("DROP TABLE IF EXISTS `$tabla`");
        if ($resultado !== false) {
            echo "<li style='color: orange;'>✓ Eliminada: {$tabla}</li>";
        } else {
            echo "<li style='color: red;'>✗ Error eliminando: {$tabla}</li>";
        }
    }
    echo '</ul>';

    // 2. Recrear tablas con esquemas correctos
    echo '<h4>Paso 2: Creando tablas con esquemas correctos</h4>';

    require_once FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/components/class-table-installer.php';

    if (class_exists('Flavor_Table_Installer')) {
        $installer = new Flavor_Table_Installer();

        $resultado = $installer->instalar('manual', [
            'modulos' => $modulos_a_recrear
        ]);

        if ($resultado['success']) {
            echo '<p style="color: green;">✓ Tablas recreadas exitosamente</p>';

            if (!empty($resultado['data']['tablas_creadas'])) {
                echo '<h4>Tablas creadas:</h4><ul>';
                foreach ($resultado['data']['tablas_creadas'] as $tabla) {
                    echo "<li style='color: green;'>✓ {$tabla}</li>";
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

    // 3. Verificar columnas
    echo '<h3>Paso 3: Verificación de Columnas</h3>';

    $verificaciones = [
        'wp_flavor_trading_ia_trades' => ['id', 'estado', 'precio_entrada', 'fecha_apertura'],
        'wp_flavor_biblioteca_libros' => ['id', 'titulo', 'estado', 'propietario_id'],
        'wp_flavor_avisos_municipales' => ['id', 'titulo', 'estado', 'fecha_expiracion'],
        'wp_flavor_expedientes' => ['id', 'numero_expediente', 'estado', 'fecha_resolucion'],
        'wp_flavor_carpooling_viajes' => ['id', 'conductor_id', 'estado', 'fecha_salida']
    ];

    echo '<ul>';
    foreach ($verificaciones as $tabla => $columnas_esperadas) {
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
            echo "<li style='color: green;'>✓ {$tabla}: Todas las columnas presentes</li>";
        } else {
            echo "<li style='color: red;'>✗ {$tabla}: Faltan columnas: " . implode(', ', $faltantes) . "</li>";
        }
    }
    echo '</ul>';
}

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '">← Volver al Compositor</a></p>';
