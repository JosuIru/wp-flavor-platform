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

// Datos de ejemplo de incidencias
$incidencias_lista = [
    [
        'id' => 1,
        'titulo' => 'Farola fundida en C/ Mayor',
        'categoria' => 'Alumbrado',
        'estado' => 'en_proceso',
        'ubicacion' => 'C/ Mayor, 45',
        'fecha' => 'Hace 2 dias',
        'votos' => 12,
        'comentarios' => 3,
        'imagen' => 'https://via.placeholder.com/400x250?text=Alumbrado',
        'porcentaje_resolucion' => 45,
    ],
    [
        'id' => 2,
        'titulo' => 'Bache grande en cruce',
        'categoria' => 'Via Publica',
        'estado' => 'pendiente',
        'ubicacion' => 'Av. Libertad esquina C/ Sol',
        'fecha' => 'Hace 3 dias',
        'votos' => 28,
        'comentarios' => 8,
        'imagen' => 'https://via.placeholder.com/400x250?text=Vía+Pública',
        'porcentaje_resolucion' => 15,
    ],
    [
        'id' => 3,
        'titulo' => 'Contenedor desbordado',
        'categoria' => 'Limpieza',
        'estado' => 'resuelto',
        'ubicacion' => 'Plaza Central',
        'fecha' => 'Hace 5 dias',
        'votos' => 15,
        'comentarios' => 2,
        'imagen' => 'https://via.placeholder.com/400x250?text=Limpieza',
        'porcentaje_resolucion' => 100,
    ],
    [
        'id' => 4,
        'titulo' => 'Grafiti en fachada historica',
        'categoria' => 'Vandalismo',
        'estado' => 'en_proceso',
        'ubicacion' => 'C/ Antigua, 12',
        'fecha' => 'Hace 1 semana',
        'votos' => 34,
        'comentarios' => 11,
        'imagen' => 'https://via.placeholder.com/400x250?text=Vandalismo',
        'porcentaje_resolucion' => 60,
    ],
    [
        'id' => 5,
        'titulo' => 'Banco roto en parque',
        'categoria' => 'Mobiliario',
        'estado' => 'pendiente',
        'ubicacion' => 'Parque Municipal',
        'fecha' => 'Hace 1 semana',
        'votos' => 8,
        'comentarios' => 1,
        'imagen' => 'https://via.placeholder.com/400x250?text=Mobiliario',
        'porcentaje_resolucion' => 20,
    ],
    [
        'id' => 6,
        'titulo' => 'Semaforo averiado',
        'categoria' => 'Trafico',
        'estado' => 'resuelto',
        'ubicacion' => 'Av. Principal, 100',
        'fecha' => 'Hace 2 semanas',
        'votos' => 45,
        'comentarios' => 15,
        'imagen' => 'https://via.placeholder.com/400x250?text=Tráfico',
        'porcentaje_resolucion' => 100,
    ],
];

// Configuración de estados
$estados_config = [
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
];

// Estadísticas generales
$estadisticas_generales = [
    [
        'numero' => '156',
        'etiqueta' => 'Total',
        'color' => 'gray',
        'icono' => '📊',
    ],
    [
        'numero' => '42',
        'etiqueta' => 'Pendientes',
        'color' => 'yellow',
        'icono' => '⏳',
    ],
    [
        'numero' => '38',
        'etiqueta' => 'En Proceso',
        'color' => 'blue',
        'icono' => '🔧',
    ],
    [
        'numero' => '76',
        'etiqueta' => 'Resueltas',
        'color' => 'green',
        'icono' => '✅',
    ],
];

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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <?php foreach ($estadisticas_generales as $stat): ?>
                <div class="flavor-stat-card bg-white rounded-xl p-5 shadow-md border-l-4 border-<?php echo esc_attr($stat['color']); ?>-500 text-center hover:shadow-lg transition-shadow">
                    <div class="text-3xl mb-2"><?php echo esc_html($stat['icono']); ?></div>
                    <div class="text-3xl font-bold text-gray-900 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo esc_html($stat['etiqueta']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

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
        <div class="flavor-incidencias-grid grid <?php echo esc_attr($grid_clase); ?> gap-6 mb-10">
            <?php foreach ($incidencias_lista as $incidencia):
                $estado_info = $estados_config[$incidencia['estado']] ?? $estados_config['pendiente'];
            ?>
                <article class="flavor-incidencia-card group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-red-300 flex flex-col h-full">
                    <!-- Imagen -->
                    <div class="flavor-imagen-contenedor relative overflow-hidden h-48 bg-gradient-to-br from-gray-200 to-gray-300">
                        <img src="<?php echo esc_url($incidencia['imagen']); ?>" alt="<?php echo esc_attr($incidencia['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">

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
