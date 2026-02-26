<?php
/**
 * Template: Mapa de Incidencias
 * Muestra las incidencias geolocalizadas con Leaflet/OSM
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_mapa = $titulo_mapa ?? 'Mapa de Incidencias';
$descripcion_mapa = $descripcion_mapa ?? 'Incidencias geolocalizadas en tu zona';
$altura_mapa = $altura_mapa ?? '600px';
$zoom_inicial = $zoom_inicial ?? 13;
$latitud_centro = $latitud_centro ?? 40.4168;
$longitud_centro = $longitud_centro ?? -3.7038;

// Obtener incidencias reales con coordenadas de la base de datos
global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$incidencias_geolocalizadas = [];

$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_incidencias)) === $tabla_incidencias;

if ($tabla_existe) {
    // Obtener incidencias que tienen coordenadas
    $incidencias_db = $wpdb->get_results(
        "SELECT id, titulo, tipo as categoria, estado, ubicacion, latitud, longitud, votos
         FROM $tabla_incidencias
         WHERE estado != 'eliminada'
         AND latitud IS NOT NULL
         AND longitud IS NOT NULL
         ORDER BY created_at DESC
         LIMIT 20"
    );

    // Normalizar estados
    $estados_normalizados = [
        'pending' => 'pendiente',
        'in_progress' => 'en_proceso',
        'resolved' => 'resuelto',
        'closed' => 'resuelto',
    ];

    foreach ($incidencias_db as $inc) {
        $estado = $estados_normalizados[$inc->estado] ?? $inc->estado;
        $incidencias_geolocalizadas[] = [
            'id' => intval($inc->id),
            'titulo' => $inc->titulo,
            'categoria' => ucfirst($inc->categoria ?? 'General'),
            'estado' => $estado,
            'ubicacion' => $inc->ubicacion ?? '',
            'latitud' => floatval($inc->latitud),
            'longitud' => floatval($inc->longitud),
            'votos' => intval($inc->votos ?? 0),
        ];
    }

    // Centrar mapa en la primera incidencia si existe
    if (!empty($incidencias_geolocalizadas)) {
        $latitud_centro = $incidencias_geolocalizadas[0]['latitud'];
        $longitud_centro = $incidencias_geolocalizadas[0]['longitud'];
    }
}

$tiene_incidencias = !empty($incidencias_geolocalizadas);

// Configuración de categorías y colores para marcadores
$categorias_colores = [
    'Alumbrado' => '#FFC107',
    'Via Publica' => '#FF5722',
    'Limpieza' => '#4CAF50',
    'Vandalismo' => '#F44336',
    'Mobiliario' => '#2196F3',
    'Trafico' => '#9C27B0',
    'default' => '#757575',
];

// Estados e iconos
$estados_icono = [
    'pendiente' => '⏳',
    'en_proceso' => '🔧',
    'resuelto' => '✅',
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="flavor-container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.553-.894L9 7m0 0l6-4m0 0l6 4m0 0v11.382a1 1 0 01-1.553.894L15 13m0 0l-6 4m0 0l-6-4"/>
                </svg>
                <?php echo esc_html__('Mapa de Incidencias', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo_mapa); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion_mapa); ?></p>
        </div>

        <!-- Contenedor del mapa -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <!-- Leyenda de categorías -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-900 mb-4"><?php echo esc_html__('Categorías', 'flavor-chat-ia'); ?></h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <?php foreach ($categorias_colores as $categoria_nombre => $color): ?>
                        <?php if ($categoria_nombre !== 'default'): ?>
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full shadow-md" style="background-color: <?php echo esc_attr($color); ?>; border: 2px solid white;"></div>
                                <span class="text-xs font-medium text-gray-700"><?php echo esc_html($categoria_nombre); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mapa -->
            <div id="flavor-incidencias-map" class="flavor-mapa-contenedor" style="height: <?php echo esc_attr($altura_mapa); ?>; width: 100%; position: relative; background: #e5e3df;">
                <div class="absolute inset-0 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.553-.894L9 7m0 0l6-4m0 0l6 4m0 0v11.382a1 1 0 01-1.553.894L15 13m0 0l-6 4m0 0l-6-4"/>
                        </svg>
                        <p><?php echo esc_html__('Cargando mapa...', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Panel de información de incidencias -->
            <div class="p-6 bg-white border-t border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4"><?php echo esc_html__('Incidencias Cercanas', 'flavor-chat-ia'); ?></h3>
                <?php if ($tiene_incidencias): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($incidencias_geolocalizadas as $incidencia): ?>
                        <div class="flavor-incidencia-card p-4 rounded-lg border border-gray-200 hover:border-red-400 hover:shadow-md transition-all duration-200 cursor-pointer">
                            <div class="flex items-start gap-3 mb-2">
                                <div class="text-2xl flex-shrink-0">
                                    <?php echo isset($estados_icono[$incidencia['estado']]) ? $estados_icono[$incidencia['estado']] : '❓'; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 line-clamp-2"><?php echo esc_html($incidencia['titulo']); ?></h4>
                                    <p class="text-xs text-gray-600 flex items-center gap-1 mt-1">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        <?php echo esc_html($incidencia['ubicacion']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold text-gray-700 bg-gray-100"><?php echo esc_html($incidencia['categoria']); ?></span>
                                <span class="flex items-center gap-1 text-xs text-gray-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                    <?php echo esc_html($incidencia['votos']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">📍</div>
                    <p><?php echo esc_html__('No hay incidencias geolocalizadas en este momento.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles del mapa -->
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <button class="flavor-button flavor-button-primary px-6 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <?php echo esc_html__('Reportar Nueva Incidencia', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-6 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
</section>

<script>
// Script para inicializar el mapa con Leaflet
(function() {
    // Datos del mapa para pasar a JavaScript
    const datosIncidencias = <?php echo json_encode($incidencias_geolocalizadas); ?>;
    const coordenadas = {
        lat: <?php echo json_encode($latitud_centro); ?>,
        lng: <?php echo json_encode($longitud_centro); ?>,
        zoom: <?php echo json_encode($zoom_inicial); ?>
    };
    const coloresCategoria = <?php echo json_encode($categorias_colores); ?>;

    // Aquí se inicializaría el mapa con Leaflet si está disponible
    // Esta funcionalidad requeriría la carga de Leaflet CSS/JS en el header del plugin
})();
</script>
