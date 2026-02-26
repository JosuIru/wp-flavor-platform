<?php
/**
 * Template: Grid Alternativo de Incidencias
 * Vista en grid para mostrar incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_grid = $titulo_grid ?? 'Incidencias';
$descripcion_grid = $descripcion_grid ?? 'Consulta el estado de las incidencias reportadas';
$columnas = $columnas ?? 3;
$mostrar_filtros = $mostrar_filtros ?? true;

// Función auxiliar para generar SVG placeholder (inline para evitar dependencias)
if (!function_exists('flavor_incidencias_placeholder_svg')) {
    function flavor_incidencias_placeholder_svg($texto, $ancho = 400, $alto = 250) {
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        $font_size = min($ancho / 10, 24);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $alto . '" viewBox="0 0 ' . $ancho . ' ' . $alto . '">';
        $svg .= '<rect fill="#e2e8f0" width="' . $ancho . '" height="' . $alto . '"/>';
        $svg .= '<text fill="#64748b" font-family="system-ui, sans-serif" font-size="' . $font_size . '" font-weight="500" ';
        $svg .= 'x="50%" y="50%" dominant-baseline="middle" text-anchor="middle">' . $texto . '</text>';
        $svg .= '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

// Obtener incidencias reales de la base de datos
global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$incidencias_lista = [];

// Verificar si existe la tabla
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_incidencias)) === $tabla_incidencias;

if ($tabla_existe) {
    // Consultar incidencias públicas (no eliminadas)
    $incidencias_db = $wpdb->get_results(
        "SELECT * FROM $tabla_incidencias
         WHERE estado != 'eliminada'
         ORDER BY created_at DESC
         LIMIT 12"
    );

    // Normalizar estados para el porcentaje de resolución
    $porcentajes_estado = [
        'pendiente' => 10,
        'pending' => 10,
        'en_proceso' => 50,
        'in_progress' => 50,
        'resuelta' => 100,
        'resolved' => 100,
        'cerrada' => 100,
        'closed' => 100,
    ];

    foreach ($incidencias_db as $incidencia) {
        // Obtener imagen o generar placeholder
        $imagen = '';
        if (!empty($incidencia->imagen)) {
            $imagen = $incidencia->imagen;
        } elseif (!empty($incidencia->tipo)) {
            $imagen = flavor_incidencias_placeholder_svg(ucfirst($incidencia->tipo), 400, 250);
        } else {
            $imagen = flavor_incidencias_placeholder_svg('Incidencia', 400, 250);
        }

        // Calcular tiempo transcurrido
        $fecha_legible = human_time_diff(strtotime($incidencia->created_at), current_time('timestamp'));

        $incidencias_lista[] = [
            'id' => $incidencia->id,
            'titulo' => $incidencia->titulo,
            'categoria' => ucfirst($incidencia->tipo ?? 'General'),
            'estado' => $incidencia->estado,
            'ubicacion' => $incidencia->ubicacion ?? '',
            'fecha' => sprintf(__('Hace %s', 'flavor-chat-ia'), $fecha_legible),
            'votos' => intval($incidencia->votos ?? 0),
            'comentarios' => intval($incidencia->comentarios ?? 0),
            'imagen' => $imagen,
            'porcentaje_resolucion' => $porcentajes_estado[$incidencia->estado] ?? 25,
        ];
    }
}

// Si no hay incidencias, mostrar mensaje vacío
$tiene_incidencias = !empty($incidencias_lista);

// Configuración de estados (español e inglés)
$estados_config = [
    // Estados en español
    'pendiente' => [
        'texto' => 'Pendiente',
        'color' => 'yellow',
        'icono' => '⏳',
        'bg_clase' => 'bg-yellow-100',
        'text_clase' => 'text-yellow-700',
    ],
    'en_proceso' => [
        'texto' => 'En Proceso',
        'color' => 'blue',
        'icono' => '🔧',
        'bg_clase' => 'bg-blue-100',
        'text_clase' => 'text-blue-700',
    ],
    'resuelto' => [
        'texto' => 'Resuelto',
        'color' => 'green',
        'icono' => '✅',
        'bg_clase' => 'bg-green-100',
        'text_clase' => 'text-green-700',
    ],
    'resuelta' => [
        'texto' => 'Resuelta',
        'color' => 'green',
        'icono' => '✅',
        'bg_clase' => 'bg-green-100',
        'text_clase' => 'text-green-700',
    ],
    'cerrada' => [
        'texto' => 'Cerrada',
        'color' => 'gray',
        'icono' => '📋',
        'bg_clase' => 'bg-gray-100',
        'text_clase' => 'text-gray-700',
    ],
    // Estados en inglés (para datos existentes en BD)
    'pending' => [
        'texto' => 'Pendiente',
        'color' => 'yellow',
        'icono' => '⏳',
        'bg_clase' => 'bg-yellow-100',
        'text_clase' => 'text-yellow-700',
    ],
    'in_progress' => [
        'texto' => 'En Proceso',
        'color' => 'blue',
        'icono' => '🔧',
        'bg_clase' => 'bg-blue-100',
        'text_clase' => 'text-blue-700',
    ],
    'resolved' => [
        'texto' => 'Resuelta',
        'color' => 'green',
        'icono' => '✅',
        'bg_clase' => 'bg-green-100',
        'text_clase' => 'text-green-700',
    ],
    'closed' => [
        'texto' => 'Cerrada',
        'color' => 'gray',
        'icono' => '📋',
        'bg_clase' => 'bg-gray-100',
        'text_clase' => 'text-gray-700',
    ],
];

// Estadísticas generales desde la base de datos
$estadisticas_generales = [];

if ($tabla_existe) {
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado != 'eliminada'");
    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'pending')");
    $en_proceso = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('en_proceso', 'in_progress')");
    $resueltas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')");

    $estadisticas_generales = [
        [
            'numero' => $total ?: '0',
            'etiqueta' => __('Total', 'flavor-chat-ia'),
            'color' => 'gray',
            'icono' => '📊',
        ],
        [
            'numero' => $pendientes ?: '0',
            'etiqueta' => __('Pendientes', 'flavor-chat-ia'),
            'color' => 'yellow',
            'icono' => '⏳',
        ],
        [
            'numero' => $en_proceso ?: '0',
            'etiqueta' => __('En Proceso', 'flavor-chat-ia'),
            'color' => 'blue',
            'icono' => '🔧',
        ],
        [
            'numero' => $resueltas ?: '0',
            'etiqueta' => __('Resueltas', 'flavor-chat-ia'),
            'color' => 'green',
            'icono' => '✅',
        ],
    ];
}

// Validar número de columnas
$columnas_validas = [1, 2, 3, 4];
$columnas = in_array($columnas, $columnas_validas) ? $columnas : 3;
$grid_clase = match($columnas) {
    1 => 'grid-cols-1',
    2 => 'grid-cols-1 md:grid-cols-2',
    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
};
?>

<section class="flavor-component py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="flavor-container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <?php echo esc_html__('Vista en Grid', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo_grid); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion_grid); ?></p>
        </div>

        <!-- Estadísticas -->
        <?php if (!empty($estadisticas_generales)): ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <?php foreach ($estadisticas_generales as $stat): ?>
                <div class="flavor-stat-card bg-white rounded-xl p-5 shadow-md border-l-4 border-<?php echo esc_attr($stat['color']); ?>-500 text-center hover:shadow-lg transition-shadow">
                    <div class="text-3xl mb-2"><?php echo esc_html($stat['icono']); ?></div>
                    <div class="text-3xl font-bold text-gray-900 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo esc_html($stat['etiqueta']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <?php if ($mostrar_filtros): ?>
            <div class="flavor-filtros mb-8 flex flex-wrap justify-center gap-2">
                <button class="flavor-filtro-btn px-4 py-2 rounded-full text-sm font-medium transition-all" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <?php echo esc_html__('Todas', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-filtro-btn px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition-colors">
                    <?php echo esc_html__('⏳ Pendientes', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-filtro-btn px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">
                    <?php echo esc_html__('🔧 En Proceso', 'flavor-chat-ia'); ?>
                </button>
                <button class="flavor-filtro-btn px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-700 hover:bg-green-200 transition-colors">
                    <?php echo esc_html__('✅ Resueltas', 'flavor-chat-ia'); ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Grid de Incidencias -->
        <?php if ($tiene_incidencias): ?>
        <div class="flavor-incidencias-grid grid <?php echo esc_attr($grid_clase); ?> gap-6 mb-10">
            <?php foreach ($incidencias_lista as $incidencia):
                $estado_info = $estados_config[$incidencia['estado']] ?? $estados_config['pendiente'];
            ?>
                <article class="flavor-incidencia-card group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-red-300 flex flex-col h-full">
                    <!-- Imagen -->
                    <div class="flavor-imagen-contenedor relative overflow-hidden h-48 bg-gradient-to-br from-gray-200 to-gray-300">
                        <?php
                        // Data URIs (svg base64) no pueden usar esc_url(), usar esc_attr()
                        $imagen_src = $incidencia['imagen'];
                        $imagen_escaped = (strpos($imagen_src, 'data:') === 0) ? esc_attr($imagen_src) : esc_url($imagen_src);
                        ?>
                        <img src="<?php echo $imagen_escaped; ?>" alt="<?php echo esc_attr($incidencia['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">

                        <!-- Badge de estado -->
                        <div class="absolute top-3 right-3 flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold <?php echo esc_attr($estado_info['bg_clase']); ?> <?php echo esc_attr($estado_info['text_clase']); ?> shadow-md">
                            <span><?php echo esc_html($estado_info['icono']); ?></span>
                            <span><?php echo esc_html($estado_info['texto']); ?></span>
                        </div>

                        <!-- Badge de categoría -->
                        <div class="absolute top-3 left-3 px-3 py-1 rounded-full text-xs font-semibold bg-gray-900 text-white shadow-md">
                            <?php echo esc_html($incidencia['categoria']); ?>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-300">
                            <div class="h-full bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-500" style="width: <?php echo intval($incidencia['porcentaje_resolucion']); ?>%;"></div>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="flavor-contenido p-5 flex-1 flex flex-col">
                        <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-red-600 transition-colors">
                            <?php echo esc_html($incidencia['titulo']); ?>
                        </h3>

                        <p class="text-sm text-gray-600 flex items-center gap-1 mb-3">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <span class="truncate"><?php echo esc_html($incidencia['ubicacion']); ?></span>
                        </p>

                        <p class="text-xs text-gray-500 mb-4">
                            <?php echo esc_html($incidencia['fecha']); ?>
                        </p>

                        <!-- Estadísticas de participación -->
                        <div class="flex items-center gap-4 text-sm text-gray-600 mb-4 pt-4 border-t border-gray-200">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                                <?php echo esc_html($incidencia['votos']); ?> votos
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <?php echo esc_html($incidencia['comentarios']); ?>
                            </span>
                        </div>

                        <!-- Acciones -->
                        <div class="flavor-acciones mt-auto flex gap-2 pt-4 border-t border-gray-200">
                            <button class="flex-1 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-red-100 hover:text-red-600 transition-colors" title="<?php echo esc_attr__('Votar', 'flavor-chat-ia'); ?>">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                                <?php echo esc_html__('Votar', 'flavor-chat-ia'); ?>
                            </button>
                            <button class="flex-1 px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-blue-100 hover:text-blue-600 transition-colors" title="<?php echo esc_attr__('Comentar', 'flavor-chat-ia'); ?>">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <?php echo esc_html__('Comentar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Estado vacío -->
        <div class="text-center py-16 bg-white rounded-2xl shadow-md mb-10">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo esc_html__('No hay incidencias reportadas', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-600 mb-6"><?php echo esc_html__('Sé el primero en reportar una incidencia en tu zona.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="text-center space-y-6">
            <a href="#reportar-nueva" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <?php echo esc_html__('Reportar Nueva Incidencia', 'flavor-chat-ia'); ?>
            </a>
            <p class="text-gray-600">
                <?php echo esc_html__('¿Encontraste un problema en tu zona?', 'flavor-chat-ia'); ?>
            </p>
        </div>
    </div>
</section>
