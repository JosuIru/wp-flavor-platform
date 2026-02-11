<?php
/**
 * Template: Estadísticas de Incidencias
 * Cards con métricas de incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_estadisticas = $titulo_estadisticas ?? 'Estadísticas de Incidencias';
$descripcion_estadisticas = $descripcion_estadisticas ?? 'Métricas y tendencias de reportes';
$mostrar_graficos = $mostrar_graficos ?? true;
$mostrar_tendencias = $mostrar_tendencias ?? true;

// Datos de métricas principales
$metricas_principales = [
    [
        'id' => 'total',
        'titulo' => 'Total Incidencias',
        'valor' => '156',
        'unidad' => 'reportes',
        'cambio_porcentaje' => 12,
        'cambio_tipo' => 'aumento',
        'icono' => '📊',
        'color' => 'gray',
        'tendencia' => 'up',
    ],
    [
        'id' => 'resueltas',
        'titulo' => 'Incidencias Resueltas',
        'valor' => '76',
        'unidad' => 'completadas',
        'cambio_porcentaje' => 8,
        'cambio_tipo' => 'aumento',
        'icono' => '✅',
        'color' => 'green',
        'tendencia' => 'up',
        'porcentaje_total' => 48.7,
    ],
    [
        'id' => 'pendientes',
        'titulo' => 'Incidencias Pendientes',
        'valor' => '42',
        'unidad' => 'sin resolver',
        'cambio_porcentaje' => 3,
        'cambio_tipo' => 'reduccion',
        'icono' => '⏳',
        'color' => 'yellow',
        'tendencia' => 'down',
        'porcentaje_total' => 26.9,
    ],
    [
        'id' => 'en_proceso',
        'titulo' => 'En Proceso',
        'valor' => '38',
        'unidad' => 'en curso',
        'cambio_porcentaje' => 5,
        'cambio_tipo' => 'aumento',
        'icono' => '🔧',
        'color' => 'blue',
        'tendencia' => 'up',
        'porcentaje_total' => 24.4,
    ],
];

// Tiempo promedio de resolución
$metricas_temporales = [
    [
        'titulo' => 'Tiempo Promedio Resolución',
        'valor' => '2.5h',
        'subtitulo' => 'horas',
        'comparativa' => 'vs 3.2h hace 30 días',
        'icono' => '⏱️',
        'color' => 'purple',
        'mejora' => true,
        'porcentaje_mejora' => 21.9,
    ],
    [
        'titulo' => 'Tiempo Respuesta Inicial',
        'valor' => '24',
        'subtitulo' => 'minutos',
        'comparativa' => 'vs 35 min hace 30 días',
        'icono' => '⚡',
        'color' => 'orange',
        'mejora' => true,
        'porcentaje_mejora' => 31.4,
    ],
    [
        'titulo' => 'Satisfacción Ciudadana',
        'valor' => '4.2',
        'subtitulo' => 'de 5 estrellas',
        'comparativa' => 'basado en 89 valoraciones',
        'icono' => '⭐',
        'color' => 'amber',
        'mejora' => true,
        'porcentaje_mejora' => 5.1,
    ],
];

// Datos por categoría
$incidencias_por_categoria = [
    [
        'categoria' => 'Via Publica',
        'cantidad' => 38,
        'porcentaje' => 24.4,
        'resueltas' => 18,
        'color' => '#FF5722',
    ],
    [
        'categoria' => 'Alumbrado',
        'cantidad' => 31,
        'porcentaje' => 19.9,
        'resueltas' => 22,
        'color' => '#FFC107',
    ],
    [
        'categoria' => 'Limpieza',
        'cantidad' => 28,
        'porcentaje' => 17.9,
        'resueltas' => 25,
        'color' => '#4CAF50',
    ],
    [
        'categoria' => 'Vandalismo',
        'cantidad' => 24,
        'porcentaje' => 15.4,
        'resueltas' => 8,
        'color' => '#F44336',
    ],
    [
        'categoria' => 'Mobiliario',
        'cantidad' => 18,
        'porcentaje' => 11.5,
        'resueltas' => 3,
        'color' => '#2196F3',
    ],
    [
        'categoria' => 'Trafico',
        'cantidad' => 17,
        'porcentaje' => 10.9,
        'resueltas' => 17,
        'color' => '#9C27B0',
    ],
];

// Tendencias últimas semanas
$tendencias_ultimas_semanas = [
    ['semana' => 'Semana 1', 'reportadas' => 18, 'resueltas' => 12],
    ['semana' => 'Semana 2', 'reportadas' => 22, 'resueltas' => 15],
    ['semana' => 'Semana 3', 'reportadas' => 26, 'resueltas' => 19],
    ['semana' => 'Semana 4', 'reportadas' => 24, 'resueltas' => 20],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="flavor-container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <?php echo esc_html__('Estadísticas', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo_estadisticas); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion_estadisticas); ?></p>
        </div>

        <!-- Métricas Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($metricas_principales as $metrica): ?>
                <div class="flavor-metrica-card bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1"><?php echo esc_html($metrica['titulo']); ?></p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-bold text-gray-900"><?php echo esc_html($metrica['valor']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo esc_html($metrica['unidad']); ?></span>
                            </div>
                        </div>
                        <div class="text-4xl"><?php echo esc_html($metrica['icono']); ?></div>
                    </div>

                    <?php if (isset($metrica['porcentaje_total'])): ?>
                        <div class="mb-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600"><?php echo esc_html__('Del total', 'flavor-chat-ia'); ?></span>
                                <span class="font-semibold text-gray-700"><?php echo number_format($metrica['porcentaje_total'], 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" style="width: <?php echo esc_attr($metrica['porcentaje_total']); ?>%; background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-1 pt-3 border-t border-gray-200">
                        <?php if ($metrica['tendencia'] === 'up'): ?>
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-600">
                                +<?php echo esc_html($metrica['cambio_porcentaje']); ?>%
                            </span>
                        <?php else: ?>
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-600">
                                -<?php echo esc_html($metrica['cambio_porcentaje']); ?>%
                            </span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500"><?php echo esc_html__('vs mes anterior', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Métricas de Tiempo y Satisfacción -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <?php foreach ($metricas_temporales as $metrica_tiempo): ?>
                <div class="flavor-metrica-temporal bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 shadow-lg border border-gray-200">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-2"><?php echo esc_html($metrica_tiempo['titulo']); ?></p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-gray-900"><?php echo esc_html($metrica_tiempo['valor']); ?></span>
                                <span class="text-xs text-gray-500"><?php echo esc_html($metrica_tiempo['subtitulo']); ?></span>
                            </div>
                        </div>
                        <div class="text-3xl"><?php echo esc_html($metrica_tiempo['icono']); ?></div>
                    </div>

                    <p class="text-xs text-gray-600 mb-3"><?php echo esc_html($metrica_tiempo['comparativa']); ?></p>

                    <?php if ($metrica_tiempo['mejora']): ?>
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100">
                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-700">
                                <?php echo number_format($metrica_tiempo['porcentaje_mejora'], 1); ?>% mejor
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Incidencias por Categoría -->
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Incidencias por Categoría', 'flavor-chat-ia'); ?></h3>

            <div class="space-y-4">
                <?php foreach ($incidencias_por_categoria as $categoria): ?>
                    <div class="group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: <?php echo esc_attr($categoria['color']); ?>;"></div>
                                <span class="font-semibold text-gray-900"><?php echo esc_html($categoria['categoria']); ?></span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-gray-900"><?php echo esc_html($categoria['cantidad']); ?> reportes</div>
                                <div class="text-xs text-gray-600"><?php echo number_format($categoria['porcentaje'], 1); ?>% del total</div>
                            </div>
                        </div>

                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 group-hover:shadow-lg" style="width: <?php echo esc_attr($categoria['porcentaje']); ?>%; background-color: <?php echo esc_attr($categoria['color']); ?>;"></div>
                        </div>

                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-600">
                            <span><?php echo esc_html__('Resueltas:', 'flavor-chat-ia'); ?></span>
                            <span class="font-semibold text-green-600"><?php echo esc_html($categoria['resueltas']); ?>/<?php echo esc_html($categoria['cantidad']); ?></span>
                            <span class="text-gray-500">(<?php echo number_format(($categoria['resueltas'] / $categoria['cantidad']) * 100, 0); ?>%)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tendencias de Últimas Semanas -->
        <?php if ($mostrar_tendencias): ?>
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-12">
                <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Últimas 4 Semanas', 'flavor-chat-ia'); ?></h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Período', 'flavor-chat-ia'); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Reportadas', 'flavor-chat-ia'); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Resueltas', 'flavor-chat-ia'); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Tasa Resolución', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tendencias_ultimas_semanas as $semana):
                                $tasa_resolucion = ($semana['resueltas'] / $semana['reportadas']) * 100;
                            ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4 text-gray-900 font-medium"><?php echo esc_html($semana['semana']); ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-white font-semibold" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                            <?php echo esc_html($semana['reportadas']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                                            <?php echo esc_html($semana['resueltas']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                                <div class="h-full bg-green-500 rounded-full" style="width: <?php echo esc_attr($tasa_resolucion); ?>%;"></div>
                                            </div>
                                            <span class="text-gray-700 font-medium text-xs"><?php echo number_format($tasa_resolucion, 0); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Llamada a la Acción -->
        <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl p-8 text-white text-center">
            <h3 class="text-2xl font-bold mb-2"><?php echo esc_html__('¿Encontraste un Problema?', 'flavor-chat-ia'); ?></h3>
            <p class="mb-6 opacity-90"><?php echo esc_html__('Ayuda a tu comunidad reportando incidencias en tu zona', 'flavor-chat-ia'); ?></p>
            <a href="#reportar" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-red-600 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <?php echo esc_html__('Reportar Incidencia', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>
