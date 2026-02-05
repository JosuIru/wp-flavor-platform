<?php
/**
 * Template: Grid de Incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Incidencias Reportadas';
$descripcion = $descripcion ?? 'Estado de las incidencias en tu zona';

$incidencias = [
    ['titulo' => 'Farola fundida en C/ Mayor', 'categoria' => 'Alumbrado', 'estado' => 'en_proceso', 'ubicacion' => 'C/ Mayor, 45', 'fecha' => 'Hace 2 dias', 'votos' => 12, 'comentarios' => 3],
    ['titulo' => 'Bache grande en cruce', 'categoria' => 'Via Publica', 'estado' => 'pendiente', 'ubicacion' => 'Av. Libertad esquina C/ Sol', 'fecha' => 'Hace 3 dias', 'votos' => 28, 'comentarios' => 8],
    ['titulo' => 'Contenedor desbordado', 'categoria' => 'Limpieza', 'estado' => 'resuelto', 'ubicacion' => 'Plaza Central', 'fecha' => 'Hace 5 dias', 'votos' => 15, 'comentarios' => 2],
    ['titulo' => 'Grafiti en fachada historica', 'categoria' => 'Vandalismo', 'estado' => 'en_proceso', 'ubicacion' => 'C/ Antigua, 12', 'fecha' => 'Hace 1 semana', 'votos' => 34, 'comentarios' => 11],
    ['titulo' => 'Banco roto en parque', 'categoria' => 'Mobiliario', 'estado' => 'pendiente', 'ubicacion' => 'Parque Municipal', 'fecha' => 'Hace 1 semana', 'votos' => 8, 'comentarios' => 1],
    ['titulo' => 'Semaforo averiado', 'categoria' => 'Trafico', 'estado' => 'resuelto', 'ubicacion' => 'Av. Principal, 100', 'fecha' => 'Hace 2 semanas', 'votos' => 45, 'comentarios' => 15],
];

$estados = [
    'pendiente' => ['texto' => 'Pendiente', 'color' => 'yellow', 'icono' => '⏳'],
    'en_proceso' => ['texto' => 'En Proceso', 'color' => 'blue', 'icono' => '🔧'],
    'resuelto' => ['texto' => 'Resuelto', 'color' => 'green', 'icono' => '✅'],
];

$stats = [
    ['numero' => '156', 'texto' => 'Total reportadas', 'color' => 'gray'],
    ['numero' => '42', 'texto' => 'Pendientes', 'color' => 'yellow'],
    ['numero' => '38', 'texto' => 'En proceso', 'color' => 'blue'],
    ['numero' => '76', 'texto' => 'Resueltas', 'color' => 'green'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-red-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Incidencias
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <?php foreach ($stats as $stat): ?>
                <div class="bg-white rounded-xl p-4 shadow-md text-center border-l-4 border-<?php echo $stat['color']; ?>-500">
                    <div class="text-3xl font-bold text-gray-900 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo esc_html($stat['texto']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <button class="px-4 py-2 rounded-full text-sm font-medium transition-all" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">Todas</button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition-colors">⏳ Pendientes</button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">🔧 En Proceso</button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-700 hover:bg-green-200 transition-colors">✅ Resueltas</button>
        </div>

        <!-- Lista de incidencias -->
        <div class="space-y-4 max-w-4xl mx-auto">
            <?php foreach ($incidencias as $incidencia): ?>
                <?php $estadoInfo = $estados[$incidencia['estado']]; ?>
                <article class="group bg-white rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 hover:border-<?php echo $estadoInfo['color']; ?>-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 text-3xl"><?php echo $estadoInfo['icono']; ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600"><?php echo esc_html($incidencia['categoria']); ?></span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-<?php echo $estadoInfo['color']; ?>-100 text-<?php echo $estadoInfo['color']; ?>-700">
                                    <?php echo esc_html($estadoInfo['texto']); ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-red-600 transition-colors mb-1"><?php echo esc_html($incidencia['titulo']); ?></h3>
                            <p class="text-sm text-gray-500 flex items-center gap-1 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <?php echo esc_html($incidencia['ubicacion']); ?>
                            </p>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span><?php echo esc_html($incidencia['fecha']); ?></span>
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
                        </div>
                        <div class="flex-shrink-0 flex flex-col gap-2">
                            <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-600 transition-colors" title="Votar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <button class="p-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-600 transition-colors" title="Comentar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12 space-y-4">
            <a href="#reportar" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Reportar Nueva Incidencia
            </a>
        </div>
    </div>
</section>
