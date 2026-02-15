<?php
/**
 * Vista: Mapa de Estaciones de Bicicletas
 * Usa el componente unificado de mapa
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

// Obtener estaciones activas
$estaciones_db = [];
if (class_exists('Flavor_Chat_Helpers') && Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
    $estaciones_db = $wpdb->get_results(
        "SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre ASC",
        ARRAY_A
    );
}

// Convertir al formato del mapa unificado
$marcadores = [];
foreach ($estaciones_db as $estacion) {
    $disponibles = (int) ($estacion['bicicletas_disponibles'] ?? 0);
    $capacidad = (int) ($estacion['capacidad_total'] ?? 0);

    // Determinar estado según disponibilidad
    $estado = 'vacio';
    if ($disponibles > 3) {
        $estado = 'disponible';
    } elseif ($disponibles > 0) {
        $estado = 'limitado';
    }

    $marcadores[] = [
        'id' => $estacion['id'],
        'nombre' => $estacion['nombre'],
        'direccion' => $estacion['direccion'] ?? '',
        'lat' => $estacion['latitud'],
        'lng' => $estacion['longitud'],
        'estado' => $estado,
        'valor' => $disponibles,
        'valor_total' => $capacidad,
        'icono' => 'dashicons-location-alt',
    ];
}

// Parámetros para el mapa unificado
$titulo = __('Mapa de Estaciones', 'flavor-chat-ia');
$subtitulo = __('Encuentra la estación más cercana para recoger o devolver una bicicleta', 'flavor-chat-ia');
$color_primario = '#0ea5e9'; // Azul cielo para bicicletas
$modulo_id = 'bicicletas';
$texto_boton = __('Cómo llegar', 'flavor-chat-ia');
$etiquetas = [
    'disponible' => __('Disponible (+3 bicis)', 'flavor-chat-ia'),
    'limitado' => __('Pocas bicis (1-3)', 'flavor-chat-ia'),
    'vacio' => __('Sin bicis', 'flavor-chat-ia'),
];

// Incluir el template unificado
$template_path = FLAVOR_CHAT_IA_PATH . 'templates/components/unified/mapa-leaflet.php';
if (file_exists($template_path)) {
    include $template_path;
} else {
    echo '<div class="flavor-error">' . esc_html__('Template de mapa no encontrado', 'flavor-chat-ia') . '</div>';
}
