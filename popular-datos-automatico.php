<?php
/**
 * Script para popular todos los módulos con datos de prueba
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/popular-datos-automatico.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Si se ejecuta desde CLI, saltear verificación de permisos
if (php_sapi_name() !== 'cli') {
    // Verificar que el usuario es administrador
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }
}

echo '<h1>Popular Datos de Prueba - Automático</h1>';

// Cargar Demo Data Manager
require_once FLAVOR_CHAT_IA_PATH . 'includes/admin/class-demo-data-manager.php';

if (!class_exists('Flavor_Demo_Data_Manager')) {
    wp_die('Clase Flavor_Demo_Data_Manager no encontrada');
}

$demo_manager = new Flavor_Demo_Data_Manager();

// Lista de módulos a popular
$modulos_a_popular = [
    'red_social' => 'Red Social',
    'network' => 'Network',
    'eventos' => 'Eventos',
    'incidencias' => 'Incidencias',
    'talleres' => 'Talleres',
    'marketplace' => 'Marketplace',
    'parkings' => 'Parkings',
    'biblioteca' => 'Biblioteca',
    'banco_tiempo' => 'Banco de Tiempo',
    'carpooling' => 'Carpooling',
    'huertos_urbanos' => 'Huertos Urbanos',
    'reciclaje' => 'Reciclaje',
    'bicicletas_compartidas' => 'Bicicletas Compartidas',
    'avisos_municipales' => 'Avisos Municipales',
    'tramites' => 'Trámites',
    'reservas' => 'Reservas',
    'socios' => 'Socios',
    'facturas' => 'Facturas',
    'fichaje_empleados' => 'Fichaje Empleados',
    'participacion' => 'Participación',
    'ayuda_vecinal' => 'Ayuda Vecinal',
    'compostaje' => 'Compostaje',
    'transparencia' => 'Transparencia'
];

echo '<h2>Populando módulos...</h2>';
echo '<div style="background: #fff; border: 1px solid #ccc; padding: 20px; margin: 20px 0;">';

$exitosos = 0;
$fallidos = 0;
$no_disponibles = 0;

foreach ($modulos_a_popular as $modulo_id => $nombre) {
    $metodo = "populate_{$modulo_id}";

    if (!method_exists($demo_manager, $metodo)) {
        echo "<p style='color: gray;'>⊘ {$nombre}: Método no disponible</p>";
        $no_disponibles++;
        continue;
    }

    echo "<p style='margin: 5px 0;'>Procesando <strong>{$nombre}</strong>...</p>";
    flush();

    try {
        $resultado = $demo_manager->$metodo();

        if ($resultado && isset($resultado['success']) && $resultado['success']) {
            echo "<p style='color: green; margin-left: 20px;'>✓ {$nombre}: " . ($resultado['message'] ?? 'Completado') . "</p>";
            $exitosos++;
        } else {
            $mensaje_error = isset($resultado['message']) ? $resultado['message'] : 'Error desconocido';
            echo "<p style='color: red; margin-left: 20px;'>✗ {$nombre}: {$mensaje_error}</p>";
            $fallidos++;
        }
    } catch (Exception $e) {
        echo "<p style='color: red; margin-left: 20px;'>✗ {$nombre}: " . $e->getMessage() . "</p>";
        $fallidos++;
    }

    flush();
}

echo '</div>';

echo '<h2>Resumen</h2>';
echo '<ul>';
echo "<li style='color: green;'>✓ Exitosos: {$exitosos}</li>";
echo "<li style='color: red;'>✗ Fallidos: {$fallidos}</li>";
echo "<li style='color: gray;'>⊘ No disponibles: {$no_disponibles}</li>";
echo '</ul>';

echo '<hr>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-app-composer') . '" class="button button-primary">← Volver al Compositor</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=flavor-unified-dashboard') . '" class="button">Ver Dashboard</a></p>';
